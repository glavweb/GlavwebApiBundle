<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Scope;

use ReflectionClass;
use InvalidArgumentException;
use ReflectionMethod;
use Glavweb\ApiBundle\Attribute\AttributeReader;
use Glavweb\ApiBundle\Attribute\Scope;

/**
 * Class ScopeReader.
 *
 * @author Nilov Andrey <nilov@glavweb.ru>
 */
class ScopeReader
{
    public function __construct(
        private readonly AttributeReader $attributeReader,
    ) {
    }

    /**
     * @return Scope[]
     */
    public function read(ReflectionClass $reflection, string $method): array
    {
        if (!$reflection->hasMethod($method)) {
            throw new InvalidArgumentException(sprintf("Class '%s' has no method '%s' method.", $reflection->getName(), $method));
        }

        return $this->getScopeAnnotationsFromMethod($reflection->getMethod($method));
    }

    /**
     * @return Scope[]
     */
    public function getScopeAnnotationsFromMethod(ReflectionMethod $method): array
    {
        return $this->attributeReader->getMethodAttributes($method, Scope::class);
    }
}
