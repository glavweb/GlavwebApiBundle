<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Middleware;

use ReflectionClass;
use ReflectionProperty;
use Doctrine\Persistence\ManagerRegistry;
use Glavweb\ApiBundle\Attribute\AttributeReader;
use Glavweb\ApiBundle\Attribute\Entity;
use Glavweb\ApiBundle\Attribute\EntityArray;
use Glavweb\ApiBundle\Exception\InvalidCommandPropertyException;
use League\Tactician\Middleware;

/**
 * Class EntityMiddleware.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class EntityMiddleware implements Middleware
{
    public function __construct(
        private readonly AttributeReader $attributeReader,
        private readonly ManagerRegistry $doctrine,
    ) {
    }

    public function execute($command, callable $next): mixed
    {
        $reflectionClass = new ReflectionClass($command::class);
        $properties = $reflectionClass->getProperties();

        foreach ($properties as $property) {
            $configuration = $this->attributeReader->getPropertyAttribute($property, Entity::class);
            if ($configuration instanceof Entity) {
                $propertyName = $configuration->getName();
                if (null === $propertyName) {
                    throw new \LogicException('Не задано имя свойства сущности');
                }

                $entityObject = $this->find($property, $configuration, $command);

                $this->setProperty(
                    $reflectionClass->getProperty($propertyName),
                    $entityObject,
                    $command
                );
            }

            $configuration = $this->attributeReader->getPropertyAttribute($property, EntityArray::class);
            if ($configuration instanceof EntityArray) {
                $entityIds = $property->getValue($command);
                if (null !== $entityIds && '' !== $entityIds) {
                    $entityIds = is_string($entityIds) ? json_decode($entityIds, true) : $entityIds;

                    if (!is_array($entityIds)) {
                        throw InvalidCommandPropertyException::onCommand($command, $property->getName(), 'Wrong JSON format.');
                    }

                    $entityArray = [];
                    foreach ($entityIds as $entityId) {
                        $class = $configuration->getClass();
                        if (null === $class) {
                            throw new \LogicException('Не задан класс сущности');
                        }

                        /** @var class-string $class */
                        $entityObject = $this->findByMappingField(
                            $class,
                            $configuration->getMappingField(),
                            $entityId,
                            $property->getName(),
                            $command
                        );
                        $entityArray[] = $entityObject;
                    }

                    $propertyName = $configuration->getName();
                    if (null === $propertyName) {
                        throw new \LogicException('Не задано имя свойства сущности');
                    }

                    $this->setProperty(
                        $reflectionClass->getProperty($propertyName),
                        $entityArray,
                        $command
                    );
                }
            }
        }

        $result = $next($command);

        return $result;
    }

    private function setProperty(ReflectionProperty $property, mixed $value, object $command): void
    {
        $property->setAccessible(true);
        $property->setValue($command, $value);
    }

    /**
     * @throws InvalidCommandPropertyException
     */
    private function find(ReflectionProperty $property, Entity $configuration, object $command): ?object
    {
        $value = $property->getValue($command);

        if (!$value || 'null' === $value) {
            return null;
        }

        $class = $configuration->getClass();
        if (null === $class) {
            throw new \LogicException('Не задан класс сущности');
        }

        /* @var class-string $class */
        return $this->findByMappingField(
            $class,
            $configuration->getMappingField(),
            $value,
            $property->getName(),
            $command
        );
    }

    /**
     * @param class-string $class
     *
     * @throws InvalidCommandPropertyException
     */
    private function findByMappingField(string $class, string $mappingField, mixed $value, string $propertyName, object $command): object
    {
        $repository = $this->doctrine->getRepository($class);
        $entityObject = $repository->findOneBy([$mappingField => $value]);

        if (!$entityObject) {
            throw InvalidCommandPropertyException::onCommand($command, $propertyName, 'Not found.');
        }

        return $entityObject;
    }
}
