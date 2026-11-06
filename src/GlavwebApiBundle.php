<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle;

use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Class GlavwebApiBundle.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class GlavwebApiBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->setParameter('glavweb_api.maker.entity_namespace', $config['maker']['entity_namespace']);
        $container->import('../config/services.yaml');

        if (class_exists(AbstractMaker::class)) {
            $container->import('../config/services_maker.yaml');
        }

        if (class_exists('Glavweb\\UploaderBundle\\Controller\\UploadController')) {
            $container->import('../config/services_uploader.yaml');
        }
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('maker')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('entity_namespace')->defaultValue('App\\Entity\\')->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
