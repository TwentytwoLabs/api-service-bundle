<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use TwentytwoLabs\ApiServiceBundle\DependencyInjection\Compiler\FormatPass;
use TwentytwoLabs\ApiServiceBundle\DependencyInjection\Compiler\PaginatorCompilerPass;

/**
 * Class ApiServiceBundle.
 */
class ApiServiceBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container
            ->addCompilerPass(new FormatPass())
            ->addCompilerPass(new PaginatorCompilerPass())
        ;
    }
}
