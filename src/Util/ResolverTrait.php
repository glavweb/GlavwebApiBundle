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

namespace Glavweb\ApiBundle\Util;

use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Ener-Getick <egetick@gmail.com>
 *
 * @internal do not use this trait or its functions in your code
 */
trait ResolverTrait
{
    /**
     * @return mixed
     */
    private function resolveValue(ContainerInterface $container, mixed $value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $value[$key] = $this->resolveValue($container, $val);
            }

            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        $escapedValue = preg_replace_callback('/%%|%([^%\s]++)%/', static function ($match) use ($container): string {
            // skip %%
            if (!isset($match[1])) {
                return '%%';
            }

            $resolved = $container->getParameter($match[1]);
            if (is_string($resolved) || is_numeric($resolved)) {
                return (string) $resolved;
            }

            throw new RuntimeException(sprintf('The container parameter "%s" must be a string or numeric, but it is of type %s.', $match[1], gettype($resolved)));
        }, $value);

        if (null === $escapedValue) {
            throw new \LogicException('Не удалось обработать параметр');
        }

        return str_replace('%%', '%', $escapedValue);
    }
}
