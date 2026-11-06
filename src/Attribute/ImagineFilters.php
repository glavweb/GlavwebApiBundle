<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Attribute;

use Attribute;

/**
 * Class ImagineFilters.
 *
 * @author Sergey Zvyagintsev <nitron.ru@gmail.com>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class ImagineFilters
{
    /**
     * @param list<string>             $filters
     * @param string|list<string>|null $property
     */
    public function __construct(
        public array $filters = [],
        public string|array|null $property = null,
    ) {
    }

    /**
     * @return list<string>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getProperty(): string|array|null
    {
        return $this->property;
    }
}
