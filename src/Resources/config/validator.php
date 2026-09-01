<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use JsonSchema\Validator;
use TwentytwoLabs\ApiValidator\Validator\MessageValidator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->private()
    ;

    $services->set('api_service.validator.json_schema_validator', Validator::class);

    $services->set('api_service.validator.message', MessageValidator::class)
        ->args([
            service('api_service.validator.json_schema_validator'),
            service('api_service.serializer.decoder'),
        ])
    ;
};
