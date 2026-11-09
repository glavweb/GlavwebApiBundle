<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Prepends Glavweb NelmioApiDoc Twig overrides so they win over vendor NelmioApiDocBundle,
 * while app templates/bundles/NelmioApiDocBundle still take precedence when present.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
final class TwigNelmioApiDocOverridesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('twig.loader.native_filesystem')) {
            return;
        }

        $path = \dirname(__DIR__, 3).'/templates/NelmioApiDoc';
        if (!is_dir($path)) {
            return;
        }

        $container->getDefinition('twig.loader.native_filesystem')
            ->addMethodCall('prependPath', [$path, 'NelmioApiDoc']);
    }
}
