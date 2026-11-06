<?php

namespace Glavweb\ApiBundle\Util;

use stdClass;
use ReflectionClass;
use ReflectionProperty;
use ReflectionNamedType;
use ReflectionUnionType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Glavweb\ApiBundle\Attribute\AttributeReader;
use Glavweb\ApiBundle\Attribute\EntityField;
use Glavweb\ApiBundle\Attribute\FromRequest;
use Glavweb\ApiBundle\Attribute\SystemRequestField;
use Glavweb\ApiBundle\Exception\InvalidCommandPropertyException;
use Glavweb\ExtraBundle\Http\RequestBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Populator.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class Populator
{
    private readonly stdClass $skipAssignment;

    public function __construct(
        private readonly AttributeReader $attributeReader,
    ) {
        $this->skipAssignment = new stdClass();
    }

    /**
     * @throws InvalidCommandPropertyException
     */
    public function fromRequest(object $object, Request $request, array $forceRewrite = []): void
    {
        $reflectionClass = new ReflectionClass($object);
        $properties = $reflectionClass->getProperties();

        foreach ($properties as $property) {
            $fromRequest = $this->attributeReader->getPropertyAttribute($property, FromRequest::class);
            if (!$fromRequest instanceof FromRequest) {
                continue;
            }

            $systemFieldName = null;
            $systemRequestField = $this->attributeReader->getPropertyAttribute($property, SystemRequestField::class);
            if ($systemRequestField instanceof SystemRequestField) {
                $systemFieldName = $systemRequestField->systemName;
            }

            $propertyName = $systemFieldName ?: ($fromRequest->name ?: $property->getName());

            if (isset($forceRewrite[$propertyName])) {
                $value = $forceRewrite[$propertyName];
            } else {
                $value = RequestBag::get($request, $propertyName);
            }

            if ($fromRequest->json) {
                if ('' === $value) {
                    $value = null;
                } elseif ($value) {
                    $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                }
            }

            if (null === $value) {
                continue;
            }

            $value = $this->coerceToPropertyType($property, $object, $propertyName, $value);
            if ($value === $this->skipAssignment) {
                continue;
            }

            $property->setAccessible(true);
            $property->setValue($object, $value);
        }
    }

    public function fromCommand(object $object, object $command, bool $asPatch, array $forceRewrite = []): void
    {
        $commandReflectionClass = new ReflectionClass($command);
        $commandProperties = $commandReflectionClass->getProperties();

        foreach ($commandProperties as $commandProperty) {
            $entityField = $this->attributeReader->getPropertyAttribute($commandProperty, EntityField::class);
            if (!$entityField instanceof EntityField) {
                continue;
            }

            $entityFieldName = $entityField->getName() ?: $commandProperty->getName();
            if (array_key_exists($entityFieldName, $forceRewrite)) {
                $value = $forceRewrite[$entityFieldName];
            } else {
                $methodGet = $entityField->getMethodGet();
                if ($methodGet) {
                    $value = $command->$methodGet();
                } else {
                    $value = $this->getPropertyValue($command, $commandProperty);
                }
            }

            if (!$asPatch || null !== $value) {
                $this->updateEntityField($object, $entityFieldName, $value, $entityField);
            }
        }
    }

    private function updateEntityField(object $object, string $entityFieldName, mixed $value, EntityField $entityField): void
    {
        if (is_array($value)) {
            $method = $entityField->getMethodSet();
        } else {
            $method = $entityField->getMethodSet() ?: 'set'.ucfirst($entityFieldName);
        }

        if ($method) {
            $object->$method($value);
        } else {
            $objectReflectionClass = new ReflectionClass($object);
            $objectProperty = $objectReflectionClass->getProperty($entityFieldName);

            $this->setPropertyValue($object, $objectProperty, $value);
        }
    }

    private function getPropertyValue(object $command, ReflectionProperty $property): mixed
    {
        $isNotPublic = !$property->isPublic();
        if ($isNotPublic) {
            $property->setAccessible(true);
        }

        $value = $property->getValue($command);

        if ($isNotPublic) {
            $property->setAccessible(false);
        }

        return $value;
    }

    private function setPropertyValue(object $command, ReflectionProperty $property, mixed $value): void
    {
        if (\is_array($value)) {
            $value = $this->coerceArrayToCollectionIfNeeded($property, $value);
        }

        $isNotPublic = !$property->isPublic();
        if ($isNotPublic) {
            $property->setAccessible(true);
        }

        $property->setValue($command, $value);

        if ($isNotPublic) {
            $property->setAccessible(false);
        }
    }

    /**
     * EntityMiddleware resolves #[EntityArray] to plain arrays, while Doctrine
     * association properties are typed as Collection after native types.
     */
    private function coerceArrayToCollectionIfNeeded(ReflectionProperty $property, array $value): Collection|array
    {
        if (!$property->hasType()) {
            return $value;
        }

        $type = $property->getType();
        $namedTypes = match (true) {
            $type instanceof ReflectionNamedType => [$type],
            $type instanceof ReflectionUnionType => $type->getTypes(),
            default => [],
        };

        foreach ($namedTypes as $namedType) {
            if (!$namedType instanceof ReflectionNamedType || $namedType->isBuiltin()) {
                continue;
            }

            if (is_a($namedType->getName(), Collection::class, true)) {
                return new ArrayCollection($value);
            }
        }

        return $value;
    }

    /**
     * Request query/body values are strings. Cast them to the property native type
     * so typed Command/Query fields do not throw TypeError.
     *
     * @throws InvalidCommandPropertyException
     */
    private function coerceToPropertyType(ReflectionProperty $property, object $object, string $propertyName, mixed $value): mixed
    {
        if (!$property->hasType()) {
            return $value;
        }

        $type = $property->getType();
        if (!$type instanceof ReflectionNamedType || false === $type->isBuiltin()) {
            return $value;
        }

        $name = $type->getName();

        if ('' === $value && \in_array($name, ['int', 'float', 'bool'], true)) {
            return $type->allowsNull() ? null : $this->skipAssignment;
        }

        return match ($name) {
            'int' => is_numeric($value) ? (int) $value : throw InvalidCommandPropertyException::onCommand($object, $propertyName, 'Значение должно быть целым числом.'),
            'float' => is_numeric($value) ? (float) $value : throw InvalidCommandPropertyException::onCommand($object, $propertyName, 'Значение должно быть числом.'),
            'bool' => $this->coerceBool($value) ?? throw InvalidCommandPropertyException::onCommand($object, $propertyName, 'Значение должно быть булевым.'),
            'string' => is_scalar($value) ? (string) $value : throw InvalidCommandPropertyException::onCommand($object, $propertyName, 'Значение должно быть строкой.'),
            default => $value,
        };
    }

    private function coerceBool(mixed $value): ?bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (\is_int($value) || \is_float($value)) {
            return (bool) $value;
        }

        if (!\is_string($value)) {
            return null;
        }

        return match (strtolower($value)) {
            '1', 'true', 'on', 'yes' => true,
            '0', 'false', 'off', 'no' => false,
            default => null,
        };
    }
}
