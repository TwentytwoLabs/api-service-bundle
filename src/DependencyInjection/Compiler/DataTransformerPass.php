<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class DataTransformerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition('api_service.data_transformer');

        $ids = [];
        foreach (array_keys($container->findTaggedServiceIds('api_service.data_transformer')) as $id) {
            $ids[] = $container->getDefinition($id);
        }

        $definition->setArgument(0, $ids);
    }
}
