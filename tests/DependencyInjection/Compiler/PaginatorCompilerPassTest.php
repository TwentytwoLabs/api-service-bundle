<?php

namespace TwentytwoLabs\ApiServiceBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use TwentytwoLabs\ApiServiceBundle\DependencyInjection\Compiler\PaginatorCompilerPass;

/**
 * Class PaginatorCompilerPassTest.
 */
class PaginatorCompilerPassTest extends TestCase
{
    private ContainerBuilder $containerBuilder;
    private Definition $pagination;

    protected function setUp(): void
    {
        $this->containerBuilder = $this->createMock(ContainerBuilder::class);
        $this->pagination = $this->createMock(Definition::class);
    }

    /**
     * @dataProvider dataProviderTestShouldNotAddPagination
     */
    public function testShouldNotAddPagination(array $config)
    {
        $this->containerBuilder
            ->expects($this->once())
            ->method('getExtensionConfig')
            ->with('api_service')
            ->willReturn([$config])
        ;
        $this->containerBuilder
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with('api_service.pagination_provider')
            ->willReturn([])
        ;
        $this->containerBuilder
            ->expects($this->once())
            ->method('getDefinition')
            ->with('api_service.pagination_provider.chain')
            ->willReturn($this->pagination)
        ;

        $compiler = new PaginatorCompilerPass();
        $compiler->process($this->containerBuilder);
    }

    public function dataProviderTestShouldNotAddPagination(): array
    {
        return [
            [
                [],
            ],
            [
                ['pagination' => []],
            ],
            [
                [
                    'pagination' => [
                        'hal' => [
                            'page' => '_links.self.href.page',
                            'perPage' => 'itemsPerPage',
                            'totalPages' => '_links.last.href.page',
                            'totalItems' => 'totalItems',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testShouldAddPagination()
    {
        $this->containerBuilder
            ->expects($this->once())
            ->method('getExtensionConfig')
            ->with('api_service')
            ->willReturn([
                [
                    'pagination' => [
                        'hal' => [
                            'page' => '_links.self.href.page',
                            'perPage' => 'itemsPerPage',
                            'totalPages' => '_links.last.href.page',
                            'totalItems' => 'totalItems',
                        ],
                    ],
                ],
            ])
        ;
        $this->containerBuilder
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with('api_service.pagination_provider')
            ->willReturn([
                'foo' => [['provider' => 'hal']],
            ])
        ;
        $this->containerBuilder
            ->expects($this->exactly(2))
            ->method('getDefinition')
            ->willReturnCallback(function ($id) {
                $this->assertTrue(\in_array($id, ['api_service.pagination_provider.chain', 'foo']));

                if ('api_service.pagination_provider.chain' === $id) {
                    $this->pagination->expects($this->once())->method('replaceArgument')->willReturnCallback(function ($key, $argument) {
                        $this->assertSame(0, $key);
                        $this->assertIsArray($argument);
                        $this->assertNotEmpty($argument);

                        return $this->pagination;
                    });
                } elseif ('foo' === $id) {
                    $this->pagination->expects($this->exactly(2))->method('replaceArgument')->willReturnCallback(function ($key, $argument) {
                        $this->assertSame(0, $key);
                        $this->assertIsArray($argument);
                        $this->assertNotEmpty($argument);

                        return $this->pagination;
                    });
                }

                return $this->pagination;
            })
        ;

        $compiler = new PaginatorCompilerPass();
        $compiler->process($this->containerBuilder);
    }
}
