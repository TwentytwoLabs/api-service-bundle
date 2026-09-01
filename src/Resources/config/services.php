<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Rize\UriTemplate;
use TwentytwoLabs\ApiServiceBundle\Factory\ApiServiceFactory;
use TwentytwoLabs\ApiServiceBundle\Factory\RequestFactory;
use TwentytwoLabs\ApiValidator\Factory\CachedSchemaFactoryDecorator;
use TwentytwoLabs\ApiValidator\Factory\OpenApiSchemaFactory;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->private()
    ;

    $services->set('api_service.uri_template', UriTemplate::class);

    $services->set('api_service.factory.request', RequestFactory::class)
        ->args([
            service('api_service.request_factory'),
            service('api_service.uri_template'),
            service('api_service.uri_factory'),
            service('api_service.stream_factory'),
            service('api_service.serializer'),
        ])
    ;

    // Schema Factories
    $services->set('api_service.schema_factory.open-api', OpenApiSchemaFactory::class);

    $services->set('api_service.schema_factory.cached_factory', CachedSchemaFactoryDecorator::class)
        ->args([
            abstract_arg('schema factory to decorate'),
            abstract_arg('cache adapter'),
        ])
    ;

    // Factory used to generate API Service instances
    $services->set('api_service.factory', ApiServiceFactory::class)
        ->public()
        ->args([
            service('api_service.factory.request'),
            service('api_service.validator.message'),
            service('api_service.serializer'),
        ])
    ;
};
