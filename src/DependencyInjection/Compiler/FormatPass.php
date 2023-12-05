<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class FormatPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition('api_service.serializer.decoder.symfony');

        $ids = [];
        foreach (array_keys($container->findTaggedServiceIds('serializer.encoder')) as $id) {
            $ids[] = $container->getDefinition($id);
        }

        $definition->setArgument(0, $ids);
    }
}
