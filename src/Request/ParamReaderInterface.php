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

use ReflectionClass;
use ReflectionMethod;
use Glavweb\ApiBundle\Attribute\ParamInterface;

/**
 * Interface for loading query parameters for a method.
 *
 * @author Alexander <iam.asm89@gmail.com>
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 */
interface ParamReaderInterface
{
    /**
     * Read annotations for a given method.
     *
     * @return ParamInterface[] Param annotation objects of the method. Indexed by parameter name
     */
    public function read(ReflectionClass $reflection, string $method): array;

    /**
     * Read annotations for a given method.
     *
     * @return ParamInterface[] Param annotation objects of the method. Indexed by parameter name
     */
    public function getParamsFromMethod(ReflectionMethod $method): array;

    /**
     * @return ParamInterface[] Param annotation objects of the class. Indexed by parameter name
     */
    public function getParamsFromClass(ReflectionClass $class): array;
}
