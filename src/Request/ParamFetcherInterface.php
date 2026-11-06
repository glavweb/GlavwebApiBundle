<?php

declare(strict_types=1);

/*
 * This file is part of the FOSRestBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\ApiBundle\Request;

/**
 * Helper interface to validate query parameters from the active request.
 *
 * @author Alexander <iam.asm89@gmail.com>
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 */
interface ParamFetcherInterface
{
    public function setController(callable $controller): void;

    /**
     * Gets a validated parameter.
     *
     * @param string $name   Name of the parameter
     * @param bool   $strict Whether a requirement mismatch should cause an exception
     */
    public function get(string $name, ?bool $strict = null): mixed;

    /**
     * Gets all validated parameter.
     *
     * @param bool $strict Whether a requirement mismatch should cause an exception
     */
    public function all(?bool $strict = null): array;
}
