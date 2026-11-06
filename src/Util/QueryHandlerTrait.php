<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Util;

use Glavweb\ApiBundle\Exception\WrongSortingFormatException;

/**
 * Trait QueryHandlerTrait.
 */
trait QueryHandlerTrait
{
    protected function getOrdering(?string $inSort): array
    {
        if (empty($inSort)) {
            return [];
        }

        $sort = json_decode($inSort, true);

        if (null === $sort) {
            throw new WrongSortingFormatException('Wrong sorting format: '.$inSort.'.');
        }

        return $sort;
    }
}
