<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Imagine;

use Liip\ImagineBundle\Service\FilterService;

/**
 * Replacement for removed Liip Imagine Templating FilterHelper (Symfony Templating gone).
 */
class FilterHelper
{
    public function __construct(
        private FilterService $filterService,
    ) {
    }

    public function filter($path, $filter, array $config = [], $resolver = null): string
    {
        return $this->filterService->getUrlOfFilteredImage((string) $path, (string) $filter, $resolver);
    }
}
