<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Attribute;

use Attribute;

/**
 * Class Scope.
 *
 * @author Nilov Andrey <nilov@glavweb.ru>
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Scope
{
    public ?string $name = null;

    public ?string $path = null;

    public ?string $role = null;

    /**
     * @param string|array<string, mixed>|null $name
     */
    public function __construct(string|array|null $name = null, ?string $path = null, ?string $role = null)
    {
        if (\is_array($name)) {
            $this->name = $name['name'] ?? null;
            $this->path = $name['path'] ?? null;
            $this->role = $name['role'] ?? null;

            return;
        }

        $this->name = $name;
        $this->path = $path;
        $this->role = $role;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }
}
