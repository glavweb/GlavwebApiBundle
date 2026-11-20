<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Util;

use Glavweb\ApiBundle\Query\ApiFilter;

/**
 * Trait QueryHandlerTrait.
 */
trait QueryHandlerTrait
{
    protected function getOrdering(?string $inSort): array
    {
        return ApiFilter::ordering($inSort);
    }
}
