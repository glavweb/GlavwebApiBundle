<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Request;

use ReflectionClass;
use InvalidArgumentException;
use ReflectionMethod;
use ReflectionAttribute;
use Glavweb\ApiBundle\Attribute\AttributeReader;
use Glavweb\ApiBundle\Attribute\ParamInterface;
use Glavweb\ApiBundle\Attribute\QueryParam;
use Glavweb\ApiBundle\Attribute\RequestParam;

/**
 * Loads QueryParam / RequestParam attributes from methods and classes.
 */
class ParamReader implements ParamReaderInterface
{
    public function __construct(
        private readonly AttributeReader $attributeReader,
    ) {
    }

    public function read(ReflectionClass $reflection, string $method): array
    {
        if (!$reflection->hasMethod($method)) {
            throw new InvalidArgumentException(sprintf("Class '%s' has no method '%s'.", $reflection->getName(), $method));
        }

        $methodParams = $this->getParamsFromMethod($reflection->getMethod($method));
        $classParams = $this->getParamsFromClass($reflection);

        return array_merge($methodParams, $classParams);
    }

    public function getParamsFromMethod(ReflectionMethod $method): array
    {
        return $this->getParamsFromAnnotationArray(array_merge(
            $this->attributeReader->getMethodAttributes($method, QueryParam::class),
            $this->attributeReader->getMethodAttributes($method, RequestParam::class),
        ));
    }

    public function getParamsFromClass(ReflectionClass $class): array
    {
        return $this->getParamsFromAnnotationArray(array_merge(
            $this->getClassAttributes($class, QueryParam::class),
            $this->getClassAttributes($class, RequestParam::class),
        ));
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $attributeClass
     *
     * @return list<T>
     */
    private function getClassAttributes(ReflectionClass $class, string $attributeClass): array
    {
        $result = [];
        foreach ($class->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $result[] = $attribute->newInstance();
        }

        return $result;
    }

    /**
     * @param object[] $annotations
     *
     * @return ParamInterface[]
     */
    private function getParamsFromAnnotationArray(array $annotations): array
    {
        $params = [];
        foreach ($annotations as $annotation) {
            if ($annotation instanceof ParamInterface) {
                $params[$annotation->getName()] = $annotation;
            }
        }

        return $params;
    }
}
