<?php

declare(strict_types=1);

/*
 * This file is part of the Glavweb RestBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\ApiBundle\Scope;

use Glavweb\ApiBundle\Attribute\Scope;

/**
 * Interface ScopeFetcherInterface.
 *
 * @author Nilov Andrey <nilov@glavweb.ru>
 */
interface ScopeFetcherInterface
{
    public function setController(callable $controller): void;

    /**
     * Returns first available scope path by name.
     */
    public function getAvailable(?string $requestName = null, ?string $default = null): ?string;

    /**
     * Check role defined in scope annotation.
     */
    public function isGranted(Scope $annotation): bool;
}
