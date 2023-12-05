<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use TwentytwoLabs\ApiServiceBundle\DependencyInjection\Compiler\FormatPass;

final class FormatPassTest extends TestCase
{
    private ContainerBuilder $containerBuilder;
    private Definition $definition;

    protected function setUp(): void
    {
        $this->definition = $this->createMock(Definition::class);
        $this->containerBuilder = $this->createMock(ContainerBuilder::class);
    }

    public function testShouldNotAddFormat(): void
    {
        $this->containerBuilder
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with('serializer.encoder')
            ->willReturn([])
        ;
        $this->containerBuilder
            ->expects($this->once())
            ->method('getDefinition')
            ->with('api_service.serializer.decoder.symfony')
            ->willReturn($this->definition)
        ;
        $this->definition->expects($this->once())->method('setArgument')->with(0, [])->willReturnSelf();

        $compiler = new FormatPass();
        $compiler->process($this->containerBuilder);
    }

    public function testShouldAddFormat(): void
    {
        $this->containerBuilder
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with('serializer.encoder')
            ->willReturn(['service_id' => []])
        ;

        $matcher = $this->exactly(2);
        $this->containerBuilder
            ->expects($matcher)
            ->method('getDefinition')
            ->willReturnCallback(function (string $id) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('api_service.serializer.decoder.symfony', $id);
                }

                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('service_id', $id);
                }

                return $this->definition;
            })
        ;
        $this->definition
            ->expects($this->once())
            ->method('setArgument')
            ->willReturnCallback(function ($position, $argument) {
                $this->assertSame(0, $position);
                $this->assertTrue(\is_array($argument));
                $this->assertCount(1, $argument);
                $this->assertInstanceOf(Definition::class, $argument[0]);

                return $this->definition;
            })
        ;

        $compiler = new FormatPass();
        $compiler->process($this->containerBuilder);
    }
}
