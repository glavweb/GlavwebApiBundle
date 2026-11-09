<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle;

use Glavweb\ApiBundle\DependencyInjection\Compiler\TwigNelmioApiDocOverridesPass;
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
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new TwigNelmioApiDocOverridesPass());
    }

    public function boot(): void
    {
        /** @var string $class */
        $class = $this->container->getParameter('glavweb_api.error_codes_class');
        if ('' === $class) {
            $class = ErrorCodes::class;
        }

        if (!class_exists($class)) {
            throw new \LogicException(sprintf('glavweb_api.error_codes_class "%s" does not exist.', $class));
        }

        ErrorCodes::setCatalogClass($class);
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->setParameter('glavweb_api.error_codes_class', $config['error_codes_class']);
        $builder->setParameter('glavweb_api.exceptions', $config['exceptions']);
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
                ->scalarNode('error_codes_class')
                    ->info('Subclass of Glavweb\ApiBundle\ErrorCodes with project codes (e.g. App\ErrorCodes). Empty = bundle catalog only.')
                    ->defaultValue('')
                ->end()
                ->arrayNode('exceptions')
                    ->info('Exception FQCN => HTTP status / client message for the JSON ExceptionListener (merged with bundle defaults, matched by instanceof).')
                    ->useAttributeAsKey('class')
                    ->arrayPrototype()
                        ->children()
                            ->integerNode('status')->isRequired()->min(400)->max(599)->end()
                            ->scalarNode('message')
                                ->info('Fixed client message. null = use the exception message.')
                                ->defaultNull()
                            ->end()
                        ->end()
                    ->end()
                ->end()
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
