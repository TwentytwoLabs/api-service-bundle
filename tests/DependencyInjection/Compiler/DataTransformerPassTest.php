<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use TwentytwoLabs\ApiServiceBundle\DependencyInjection\Compiler\DataTransformerPass;

final class DataTransformerPassTest extends TestCase
{
    public function testShouldNotAddDataTransformer(): void
    {
        $definition = $this->createMock(Definition::class);
        $definition->expects($this->once())->method('setArgument')->with(0, [])->willReturnSelf();

        $containerBuilder = $this->createMock(ContainerBuilder::class);

        $containerBuilder
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with('api_service.data_transformer')
            ->willReturn([])
        ;
        $containerBuilder
            ->expects($this->once())
            ->method('getDefinition')
            ->with('api_service.data_transformer')
            ->willReturn($definition)
        ;

        $compiler = $this->getCompilerPass();
        $compiler->process($containerBuilder);
    }

    public function testShouldAddDataTransformer(): void
    {
        $definition = $this->createMock(Definition::class);
        $definition
            ->expects($this->once())
            ->method('setArgument')
            ->willReturnCallback(function ($position, $argument) use ($definition) {
                $this->assertSame(0, $position);
                $this->assertTrue(\is_array($argument));
                $this->assertCount(1, $argument);
                $this->assertInstanceOf(Definition::class, $argument[0]);

                return $definition;
            })
        ;

        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $containerBuilder
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with('api_service.data_transformer')
            ->willReturn(['service_id' => []])
        ;

        $matcher = $this->exactly(2);
        $containerBuilder
            ->expects($matcher)
            ->method('getDefinition')
            ->willReturnCallback(function (string $id) use ($matcher, $definition) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('api_service.data_transformer', $id);
                }

                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('service_id', $id);
                }

                return $definition;
            })
        ;

        $compiler = $this->getCompilerPass();
        $compiler->process($containerBuilder);
    }

    private function getCompilerPass(): DataTransformerPass
    {
        return new DataTransformerPass();
    }
}
