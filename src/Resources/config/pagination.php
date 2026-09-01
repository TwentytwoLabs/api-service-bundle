<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use TwentytwoLabs\ApiServiceBundle\Factory\Pagination\HalPaginationFactory;
use TwentytwoLabs\ApiServiceBundle\Factory\Pagination\HeaderPaginationFactory;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->private()
    ;

    $services->set('api_service.factory.pagination.hal', HalPaginationFactory::class);
    $services->set('api_service.factory.pagination.header', HeaderPaginationFactory::class);
};
