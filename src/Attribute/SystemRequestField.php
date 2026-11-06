<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Attribute;

use Attribute;

/**
 * Class SystemRequestField.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class SystemRequestField
{
    public ?string $systemName = null;

    /**
     * @param string|array<string, mixed>|null $systemName
     */
    public function __construct(string|array|null $systemName = null)
    {
        if (\is_array($systemName)) {
            $this->systemName = $systemName['systemName'] ?? $systemName['value'] ?? null;

            return;
        }

        $this->systemName = $systemName;
    }
}
