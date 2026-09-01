<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\Serializer\Encoder\ChainDecoder;
use TwentytwoLabs\ApiServiceBundle\Denormalizer\ErrorDenormalizer;
use TwentytwoLabs\ApiServiceBundle\Denormalizer\ResourceDenormalizer;
use TwentytwoLabs\ApiValidator\Decoder\Adapter\SymfonyDecoderAdapter;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->private()
    ;

    $services->set('api_service.serializer.decoder.symfony', ChainDecoder::class);

    $services->set('api_service.serializer.decoder', SymfonyDecoderAdapter::class)
        ->args([
            service('api_service.serializer.decoder.symfony'),
        ])
    ;

    $services->set('api_service.denormalizer.resource', ResourceDenormalizer::class)
        ->args([
            service('api_service.data_transformer'),
        ])
        ->tag('serializer.normalizer', ['priority' => -890])
    ;

    $services->set('api_service.denormalizer.error', ErrorDenormalizer::class)
        ->tag('serializer.normalizer', ['priority' => -890])
    ;
};
