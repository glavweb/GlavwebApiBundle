<?php

declare(strict_types=1);

/*
 * This file is part of the FOSRestBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\ApiBundle\Exception;

use Glavweb\ApiBundle\Attribute\ParamInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class InvalidParameterException extends BadRequestHttpException
{
    private ?ParamInterface $parameter = null;
    private ?ConstraintViolationListInterface $violations = null;

    public function getParameter(): ?ParamInterface
    {
        return $this->parameter;
    }

    public function getViolations(): ?ConstraintViolationListInterface
    {
        return $this->violations;
    }

    public static function withViolations(ParamInterface $parameter, ConstraintViolationListInterface $violations)
    {
        $message = '';

        foreach ($violations as $key => $violation) {
            if ($key > 0) {
                $message .= "\n";
            }

            $invalidValue = $violation->getInvalidValue();

            $message .= sprintf(
                'Parameter "%s" of value "%s" violated a constraint "%s"',
                $parameter->getName(),
                is_scalar($invalidValue) ? $invalidValue : var_export($invalidValue, true),
                $violation->getMessage()
            );
        }

        return self::withViolationsAndMessage($parameter, $violations, $message);
    }

    /**
     * Do not use this method. It will be removed in 2.0.
     *
     * @return self
     *
     * @internal
     */
    public static function withViolationsAndMessage(ParamInterface $parameter, ConstraintViolationListInterface $violations, string $message)
    {
        $exception = new self($message);
        $exception->parameter = $parameter;
        $exception->violations = $violations;

        return $exception;
    }
}
