<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Exception;

use Glavweb\ApiBundle\ErrorCodes;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Client-facing API error (HTTP 420 in ExceptionListener).
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class ClientErrorException extends AppException
{
    private ?ConstraintViolationListInterface $violations = null;

    public static function withViolationList(string $message, ConstraintViolationListInterface $violations, int $code = ErrorCodes::FORM_VALIDATION_FAILED): static
    {
        $exception = new static($message, $code);
        $exception->setViolations($violations);

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
