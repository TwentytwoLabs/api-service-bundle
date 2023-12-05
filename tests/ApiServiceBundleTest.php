<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TwentytwoLabs\ApiServiceBundle\ApiServiceBundle;
use TwentytwoLabs\ApiServiceBundle\DependencyInjection\Compiler\DataTransformerPass;
use TwentytwoLabs\ApiServiceBundle\DependencyInjection\Compiler\FormatPass;

final class ApiServiceBundleTest extends TestCase
{
    public function testShouldAddCompilerPass(): void
    {
        $matcher = $this->exactly(2);

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects($matcher)
            ->method('addCompilerPass')
            ->willReturnCallback(function ($pass) use ($matcher, $container) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertInstanceOf(FormatPass::class, $pass),
                    2 => $this->assertInstanceOf(DataTransformerPass::class, $pass),
                };

                return $container;
            })
        ;

        $bundle = new ApiServiceBundle();
        $bundle->build($container);
    }
}
