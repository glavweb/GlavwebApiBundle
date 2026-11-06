<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Attribute;

use Attribute;

/**
 * Class EntityField.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class EntityField
{
    public ?string $name = null;

    public ?string $methodSet = null;

    public ?string $methodGet = null;

    /**
     * @param string|array<string, mixed>|null $name
     */
    public function __construct(
        string|array|null $name = null,
        ?string $methodSet = null,
        ?string $methodGet = null,
    ) {
        if (\is_array($name)) {
            $this->name = $name['name'] ?? $name['value'] ?? null;
            $this->methodSet = $name['methodSet'] ?? null;
            $this->methodGet = $name['methodGet'] ?? null;

            return;
        }

        $this->name = $name;
        $this->methodSet = $methodSet;
        $this->methodGet = $methodGet;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getMethodSet(): ?string
    {
        return $this->methodSet;
    }

    public function getMethodGet(): ?string
    {
        return $this->methodGet;
    }
}
