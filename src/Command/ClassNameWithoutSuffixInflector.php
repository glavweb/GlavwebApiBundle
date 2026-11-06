<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Command;

use League\Tactician\Handler\MethodNameInflector\ClassNameInflector;

/**
 * Class ClassNameWithoutSuffixInflector.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class ClassNameWithoutSuffixInflector extends ClassNameInflector
{
    private string $suffix;

    private int $suffixLength;

    /**
     * @param string $suffix The string to remove from end of each class name
     */
    public function __construct(string $suffix = 'Command')
    {
        $this->suffix = $suffix;
        $this->suffixLength = strlen($suffix);
    }

    public function inflect($command, $commandHandler): string
    {
        $methodName = parent::inflect($command, $commandHandler);

        if (substr($methodName, $this->suffixLength * -1) !== $this->suffix) {
            return $methodName;
        }

        return substr($methodName, 0, strlen($methodName) - $this->suffixLength);
    }
}
