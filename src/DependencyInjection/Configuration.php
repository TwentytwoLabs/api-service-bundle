<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\DependencyInjection;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('api_service');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('default_services')
                    ->addDefaultsIfNotSet()
                    ->info('Configure which services to use when generating API service classes')
                    ->children()
                        ->scalarNode('client')->defaultValue(ClientInterface::class)->end()
                        ->scalarNode('request_factory')->defaultValue(RequestFactoryInterface::class)->end()
                        ->scalarNode('uri_factory')->defaultValue(UriFactoryInterface::class)->end()
                        ->scalarNode('stream_factory')->defaultValue(StreamFactoryInterface::class)->end()
                        ->scalarNode('serializer')->defaultValue('serializer')->end()
                    ->end()
                ->end()
                ->arrayNode('apis')
                    ->info('Declare API services')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                    ->children()
                        ->scalarNode('schema')->info('Absolute path to the OpenAPI/Swagger2.0 schema')->isRequired()->end()
                        ->scalarNode('client')->info('Use a specific HTTP client for an API Service')->defaultValue('api_service.client')->end()
                        ->scalarNode('logger')->info('Use a specific Logger for an API Service')->defaultValue('logger')->end()
                        ->scalarNode('version')->info('Use a specific version')->defaultValue(3)->end()
                        ->arrayNode('pagination')
                            ->info('Pagination provider')
                            ->children()
                                ->scalarNode('factory')->isRequired()->cannotBeEmpty()->end()
                                ->variableNode('options')->defaultValue([])->end()
                            ->end()
                        ->end()
                        ->arrayNode('cache')
                            ->info('Activate API schemas cache')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('service')->info('The service Id that should be used for caching schemas')->isRequired()->end()
                            ->end()
                        ->end()
                        ->arrayNode('config')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('baseUri')->isRequired()->info('The uri of your service (ex: http://domain.tld)')->end()
                                ->scalarNode('validateRequest')->defaultTrue()->info('Validate the request before sending it')->end()
                                ->scalarNode('validateResponse')->defaultTrue()->info('Validate the response before sending it')->end()
                                ->scalarNode('returnResponse')->defaultFalse()->info('Return a Response object instead of a resource')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
