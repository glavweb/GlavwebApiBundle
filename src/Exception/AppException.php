<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Exception;

use Exception;
use Glavweb\ApiBundle\ErrorCodes;
use InvalidArgumentException;
use Throwable;

/**
 * Base API/application exception.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class AppException extends Exception implements AppExceptionInterface
{
    public function __construct(?string $message = '', int $code = 0, ?Throwable $previous = null, $messageVars = null)
    {
        if ($code > 0 && null === $message) {
            try {
                $message = ErrorCodes::getMessage($code, $messageVars);
            } catch (InvalidArgumentException) {
            }
        }

        parent::__construct((string) $message, $code, $previous);
    }
}
