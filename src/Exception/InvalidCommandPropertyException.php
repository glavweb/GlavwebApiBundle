<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Exception;

use Exception;

/**
 * Class InvalidCommandPropertyException.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class InvalidCommandPropertyException extends Exception
{
    protected object $command;

    protected string $property;

    protected string $propertyMessage;

    /**
     * @return static
     */
    public static function onCommand(object $command, string $property, string $propertyMessage)
    {
        $exception = new static(
            'Validation failed for '.$command::class.':'.$property.
            ' with message: '.$propertyMessage.'.'
        );

        $exception->command = $command;
        $exception->property = $property;
        $exception->propertyMessage = $propertyMessage;

        return $exception;
    }

    public function getCommand(): object
    {
        return $this->command;
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function getPropertyMessage(): string
    {
        return $this->propertyMessage;
    }
}
