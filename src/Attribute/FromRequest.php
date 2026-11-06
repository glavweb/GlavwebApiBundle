<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Attribute;

use Attribute;

/**
 * Class FromRequest.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class FromRequest
{
    public ?string $name = null;

    public bool $json = false;

    /**
     * @param string|array<string, mixed>|null $name
     */
    public function __construct(string|array|null $name = null, bool $json = false)
    {
        if (\is_array($name)) {
            $this->name = $name['name'] ?? $name['value'] ?? null;
            $this->json = (bool) ($name['json'] ?? false);

            return;
        }

        $this->name = $name;
        $this->json = $json;
    }
}
