<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Exception;

use Glavweb\ApiBundle\ErrorCodes;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Client-facing API error. HTTP status is chosen in ExceptionListener: 400, 401, 404 or 422.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class ClientErrorException extends AppException
{
    private ?ConstraintViolationListInterface $violations = null;

    /**
     * Пустой список violations допустим: exception без `fields`, только message/code.
     */
    public static function withViolationList(string $message, ConstraintViolationListInterface $violations, int $code = ErrorCodes::FORM_VALIDATION_FAILED): static
    {
        $exception = new static($message, $code);
        if ($violations->count() > 0) {
            $exception->setViolations($violations);
        }

        return $exception;
    }

    public static function byErrorCode(int $code, ?array $messageVars = null): static
    {
        $message = ErrorCodes::getMessage($code, $messageVars);

        return new static($message, $code);
    }

    public function setViolations(ConstraintViolationListInterface $violations): void
    {
        $this->violations = $violations;
    }

    public function getViolations(): ?ConstraintViolationListInterface
    {
        return $this->violations;
    }
}
