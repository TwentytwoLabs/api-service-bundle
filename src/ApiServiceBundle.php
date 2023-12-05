<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use TwentytwoLabs\ApiServiceBundle\DependencyInjection\Compiler\DataTransformerPass;
use TwentytwoLabs\ApiServiceBundle\DependencyInjection\Compiler\FormatPass;

class ApiServiceBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container
            ->addCompilerPass(new FormatPass())
            ->addCompilerPass(new DataTransformerPass())
        ;
    }
}
