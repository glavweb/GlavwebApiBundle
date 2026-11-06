<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Attribute;

use ReflectionProperty;
use ReflectionAttribute;
use ReflectionMethod;

/**
 * Читает PHP-атрибуты с свойств и методов.
 */
final class AttributeReader
{
    /**
     * @template T of object
     *
     * @param class-string<T> $attributeClass
     *
     * @return T|null
     */
    public function getPropertyAttribute(ReflectionProperty $property, string $attributeClass): ?object
    {
        $attributes = $property->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF);
        if ([] !== $attributes) {
            return $attributes[0]->newInstance();
        }

        return null;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $attributeClass
     *
     * @return list<T>
     */
    public function getPropertyAttributes(ReflectionProperty $property, string $attributeClass): array
    {
        $result = [];
        foreach ($property->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $result[] = $attribute->newInstance();
        }

        return $result;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $attributeClass
     *
     * @return T|null
     */
    public function getMethodAttribute(ReflectionMethod $method, string $attributeClass): ?object
    {
        $attributes = $method->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF);
        if ([] !== $attributes) {
            return $attributes[0]->newInstance();
        }

        return null;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $attributeClass
     *
     * @return list<T>
     */
    public function getMethodAttributes(ReflectionMethod $method, string $attributeClass): array
    {
        $result = [];
        foreach ($method->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $result[] = $attribute->newInstance();
        }

        return $result;
    }

    /**
     * @return list<object>
     */
    /**
     * @param class-string ...$attributeClasses
     *
     * @return list<object>
     */
    public function getMethodAttributesList(ReflectionMethod $method, string ...$attributeClasses): array
    {
        $result = [];
        foreach ($attributeClasses as $attributeClass) {
            foreach ($this->getMethodAttributes($method, $attributeClass) as $instance) {
                $result[] = $instance;
            }
        }

        return $result;
    }
}
