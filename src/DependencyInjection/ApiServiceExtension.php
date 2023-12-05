<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use TwentytwoLabs\ApiServiceBundle\ApiService;
use TwentytwoLabs\ApiServiceBundle\Pagination\PaginationInterface;

final class ApiServiceExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('data-transformer.xml');
        $loader->load('pagination.xml');
        $loader->load('serializer.xml');
        $loader->load('services.xml');
        $loader->load('validator.xml');

        $this->configureDefaultServices($container, $config['default_services']);
        $this->configureApiServices($container, $config['apis']);
    }

    private function configureDefaultServices(ContainerBuilder $container, array $defaultServices): void
    {
        foreach ($defaultServices as $type => $defaultService) {
            $container->setAlias(sprintf('api_service.%s', $type), $defaultService);
        }
    }

    private function configureApiServices(ContainerBuilder $container, array $apiServices): void
    {
        // Configure each api services
        $serviceFactoryRef = new Reference('api_service.factory');
        foreach ($apiServices as $name => $arguments) {
            $paginationDef = $this->configureApiServicePagination($container, $name, $arguments);
            $schemaFactoryId = $this->configureApiServiceCache($container, $arguments['version'], $arguments['cache']);

            $container
                ->register('api_service.api.'.$name, ApiService::class)
                ->setFactory([$serviceFactoryRef, 'getService'])
                ->addArgument(new Reference($arguments['client']))
                ->addArgument(new Reference($schemaFactoryId))
                ->addArgument($arguments['schema'])
                ->addArgument(new Reference($arguments['logger'], ContainerInterface::NULL_ON_INVALID_REFERENCE))
                ->addArgument($paginationDef)
                ->addArgument($arguments['config'])
                ->addTag('twentytwo-labs.api.service')
            ;

            if (method_exists($container, 'registerAliasForArgument')) {
                $container->registerAliasForArgument('api_service.api.'.$name, ApiService::class, $name);
            }
        }
    }

    private function configureApiServicePagination(ContainerBuilder $container, string $name, array $apiService): ?Definition
    {
        $pagination = $apiService['pagination'] ?? [];
        $paginationDef = null;
        if (!empty($pagination)) {
            $paginationDef = $container
                ->register(sprintf('twenty-two-labs.api_service.pagination.%s', $name), PaginationInterface::class)
                ->setFactory([new Reference($pagination['factory']), 'createPagination'])
                ->addArgument($name)
                ->addArgument($pagination['options'])
                ->setPublic(false)
            ;
        }

        return $paginationDef;
    }

    private function configureApiServiceCache(ContainerBuilder $container, int $version, array $cache): string
    {
        $schemaFactoryId = 'api_service.schema_factory.open-api';
        if (2 === $version) {
            $schemaFactoryId = 'api_service.schema_factory.swagger';
        }

        if ($cache['enabled']) {
            $schemaFactory = $container->getDefinition('api_service.schema_factory.cached_factory');
            $schemaFactory->replaceArgument(0, new Reference($cache['service']));
            $schemaFactory->replaceArgument(1, new Reference($schemaFactoryId));
            $schemaFactoryId = 'api_service.schema_factory.cached_factory';
        }

        return $schemaFactoryId;
    }
}
