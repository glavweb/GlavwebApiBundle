<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\EventListener;

use Doctrine\ORM\NoResultException;
use Glavweb\ApiBundle\ErrorCodes;
use Glavweb\ApiBundle\Exception\AppException;
use Glavweb\ApiBundle\Exception\ClientErrorException;
use Glavweb\ApiBundle\Exception\InvalidCommandPropertyException;
use Glavweb\UploaderBundle\Exception\ValidationException;
use League\Tactician\Bundle\Middleware\InvalidCommandException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * JSON API exception handler.
 *
 * Exception → HTTP status / message mapping: bundle defaults (DEFAULT_EXCEPTIONS) merged with
 * `glavweb_api.exceptions` from the app config. Lookup is by exact class first, then `instanceof`
 * in declaration order, so a project can map its own exception hierarchy without replacing the listener.
 *
 * Security `AccessDeniedException` for a request without a full-fledged token (anonymous, expired
 * token) is left to the firewall's ExceptionListener → entry point → 401; only authenticated users get 403.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class ExceptionListener
{
    /**
     * @var array<class-string, array{status: int, message: string|null}>
     */
    public const array DEFAULT_EXCEPTIONS = [
        NoResultException::class => ['status' => Response::HTTP_NOT_FOUND, 'message' => 'Объект не найден'],
        NotFoundHttpException::class => ['status' => Response::HTTP_NOT_FOUND, 'message' => 'Объект не найден'],
        AccessDeniedException::class => ['status' => Response::HTTP_FORBIDDEN, 'message' => 'Доступ запрещен'],
        AccessDeniedHttpException::class => ['status' => Response::HTTP_FORBIDDEN, 'message' => 'Доступ запрещен'],
        AuthenticationException::class => ['status' => Response::HTTP_UNAUTHORIZED, 'message' => 'Пользователь не авторизован'],
        InvalidCommandException::class => ['status' => Response::HTTP_BAD_REQUEST, 'message' => 'Переданные данные содержат ошибки.'],
        InvalidCommandPropertyException::class => ['status' => Response::HTTP_BAD_REQUEST, 'message' => 'Переданные данные содержат ошибки.'],
        ValidationException::class => ['status' => Response::HTTP_BAD_REQUEST, 'message' => null],
        ClientErrorException::class => ['status' => 420, 'message' => null],
    ];

    /**
     * @var array<class-string, array{status: int, message: string|null}>
     */
    private readonly array $exceptions;

    /**
     * @param array<class-string, array{status?: int, message?: string|null}> $exceptions app overrides (`glavweb_api.exceptions`)
     */
    public function __construct(
        private readonly bool $debug,
        private readonly string $environment,
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AuthenticationTrustResolverInterface $trustResolver,
        array $exceptions = [],
    ) {
        $merged = self::DEFAULT_EXCEPTIONS;
        foreach ($exceptions as $class => $options) {
            $merged[$class] = [
                'status' => $options['status'] ?? $merged[$class]['status'] ?? Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => \array_key_exists('message', $options) ? $options['message'] : ($merged[$class]['message'] ?? null),
            ];
        }
        $this->exceptions = $merged;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        $exception = $event->getThrowable();
        $statusCode = $exception instanceof HttpException ? $exception->getStatusCode() : $this->getStatusCodeByException($exception);
        $message = $this->getMessageByException($exception);
        $body = null;

        if ($statusCode >= 500) {
            $this->logger->critical($exception->getMessage(), ['exception' => $exception->__toString()]);
        }

        if ('json' !== $request->getRequestFormat()) {
            return;
        }

        if ($this->shouldStartAuthentication($exception)) {
            return;
        }

        if ($exception instanceof TranslatableInterface) {
            $message = $exception->trans($this->translator);
        }

        if ($exception instanceof InvalidCommandException) {
            $errorFields = [];
            foreach ($exception->getViolations() as $violation) {
                $errorFields[] = [
                    'property' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }

            $body = [
                'code' => ErrorCodes::FORM_VALIDATION_FAILED,
                'message' => $message,
                'fields' => $errorFields,
            ];
        }

        if ($exception instanceof InvalidCommandPropertyException) {
            $body = [
                'code' => ErrorCodes::FORM_VALIDATION_FAILED,
                'message' => $message,
                'fields' => [
                    [
                        'property' => $exception->getProperty(),
                        'message' => $exception->getPropertyMessage(),
                    ],
                ],
            ];
        }

        if (!$body) {
            $body = [
                'code' => $exception->getCode(),
                'message' => $message,
            ];
        }

        if ($this->debug) {
            $body['debugInfo'] = $exception->__toString();
        }

        $event->setResponse(new Response(json_encode($body), $statusCode, [
            'Content-Type' => $request->getMimeType($request->getRequestFormat()) ?: 'application/json',
        ]));
    }

    /**
     * Same rule as Symfony's security ExceptionListener::handleAccessDeniedException():
     * access denied without a full-fledged token means "authenticate first", not "forbidden".
     */
    private function shouldStartAuthentication(Throwable $exception): bool
    {
        if (!$exception instanceof AccessDeniedException) {
            return false;
        }

        return !$this->trustResolver->isFullFledged($this->tokenStorage->getToken());
    }

    private function getStatusCodeByException(Throwable $exception): int
    {
        return $this->findMapping($exception)['status'] ?? Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    private function getMessageByException(Throwable $exception): string
    {
        $mapping = $this->findMapping($exception);

        if (null !== $mapping && null !== $mapping['message']) {
            return $mapping['message'];
        }

        if (null === $mapping && 'prod' === $this->environment && !($exception instanceof AppException)) {
            return 'Неизвестная ошибка';
        }

        return $exception->getMessage();
    }

    /**
     * Exact class first, then first `instanceof` match in declaration order (defaults, then app config).
     *
     * @return array{status: int, message: string|null}|null
     */
    private function findMapping(Throwable $exception): ?array
    {
        if (isset($this->exceptions[$exception::class])) {
            return $this->exceptions[$exception::class];
        }

        foreach ($this->exceptions as $class => $mapping) {
            if ($exception instanceof $class) {
                return $mapping;
            }
        }

        return null;
    }
}
