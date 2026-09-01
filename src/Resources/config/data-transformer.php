<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use TwentytwoLabs\ApiServiceBundle\DataTransformer\DataTransformer;
use TwentytwoLabs\ApiServiceBundle\DataTransformer\HalDataTransformer;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->private()
    ;

    $services->set('api_service.data_transformer', DataTransformer::class);

    $services->set('api_service.data_transformer.hal', HalDataTransformer::class)
        ->tag('api_service.data_transformer')
    ;
};
