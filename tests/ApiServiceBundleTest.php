<?php

namespace TwentytwoLabs\ApiServiceBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TwentytwoLabs\ApiServiceBundle\ApiServiceBundle;
use TwentytwoLabs\ApiServiceBundle\DependencyInjection\Compiler\FormatPass;
use TwentytwoLabs\ApiServiceBundle\DependencyInjection\Compiler\PaginatorCompilerPass;

/**
 * Class ApiServiceBundle.
 */
class ApiServiceBundleTest extends TestCase
{
    public function testShouldAddCompilerPass()
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects($this->exactly(2))
            ->method('addCompilerPass')
            ->willReturnCallback(function ($pass) use ($container) {
                $this->assertTrue($pass instanceof FormatPass || $pass instanceof PaginatorCompilerPass);

                return $container;
            })
        ;

        $bundle = new ApiServiceBundle();
        $bundle->build($container);
    }
}
