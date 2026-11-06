<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Attribute;

use Attribute;

/**
 * Class Entity.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Entity
{
    public ?string $name = null;

    public ?string $class = null;

    public string $mappingField = 'id';

    /**
     * @param string|array<string, mixed>|null $name
     */
    public function __construct(
        string|array|null $name = null,
        ?string $class = null,
        string $mappingField = 'id',
    ) {
        if (\is_array($name)) {
            $this->name = $name['name'] ?? null;
            $this->class = $name['class'] ?? null;
            $this->mappingField = $name['mappingField'] ?? 'id';

            return;
        }

        $this->name = $name;
        $this->class = $class;
        $this->mappingField = $mappingField;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function getMappingField(): string
    {
        return $this->mappingField;
    }
}
