<?php

namespace TwentytwoLabs\ApiServiceBundle\Tests\Pagination;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use TwentytwoLabs\Api\Definition\ResponseDefinition;
use TwentytwoLabs\Api\Service\Pagination\Pagination;
use TwentytwoLabs\Api\Service\Pagination\Provider\PaginationProviderInterface;
use TwentytwoLabs\ApiServiceBundle\Pagination\PaginationProviderChain;

class PaginationProviderChainTest extends TestCase
{
    public function testShouldNotSupportPaginationBecauseThereAreNotProvider()
    {
        /** @var ResponseInterface|MockObject $response */
        $response = $this->createMock(ResponseInterface::class);

        /** @var ResponseDefinition|MockObject $responseDefinition */
        $responseDefinition = $this->getMockBuilder(ResponseDefinition::class)->disableOriginalConstructor()->getMock();

        $provider = new PaginationProviderChain([]);
        $this->assertFalse($provider->supportPagination([], $response, $responseDefinition));
    }

    public function testShouldNotSupportPagination()
    {
        $providers = [];
        for ($i = 0; $i < 1; ++$i) {
            $provider = $this->createMock(PaginationProviderInterface::class);
            $provider->expects($this->once())->method('supportPagination')->willReturn(false);
            $providers[] = $provider;
        }

        /** @var ResponseInterface|MockObject $response */
        $response = $this->createMock(ResponseInterface::class);

        /** @var ResponseDefinition|MockObject $responseDefinition */
        $responseDefinition = $this->getMockBuilder(ResponseDefinition::class)->disableOriginalConstructor()->getMock();

        $provider = new PaginationProviderChain($providers);
        $this->assertFalse($provider->supportPagination([], $response, $responseDefinition));
    }

    public function testShouldSupportPagination()
    {
        $providers = [];
        for ($i = 0; $i < 1; ++$i) {
            $provider = $this->createMock(PaginationProviderInterface::class);
            $provider->expects($this->once())->method('supportPagination')->willReturn(true);
            $providers[] = $provider;
        }

        /** @var ResponseInterface|MockObject $response */
        $response = $this->createMock(ResponseInterface::class);

        /** @var ResponseDefinition|MockObject $responseDefinition */
        $responseDefinition = $this->getMockBuilder(ResponseDefinition::class)->disableOriginalConstructor()->getMock();

        $provider = new PaginationProviderChain($providers);
        $this->assertTrue($provider->supportPagination([], $response, $responseDefinition));
    }

    public function testShouldNotReturnPaginationBecauseThrowException()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No pagination provider available');

        $data = [];
        /** @var ResponseInterface|MockObject $response */
        $response = $this->createMock(ResponseInterface::class);

        /** @var ResponseDefinition|MockObject $responseDefinition */
        $responseDefinition = $this->getMockBuilder(ResponseDefinition::class)->disableOriginalConstructor()->getMock();

        $provider = new PaginationProviderChain([]);
        $provider->getPagination($data, $response, $responseDefinition);
    }

    public function testShouldReturnPagination()
    {
        $data = [];
        $pagination = $this->getMockBuilder(Pagination::class)->disableOriginalConstructor()->getMock();

        $provider = $this->createMock(PaginationProviderInterface::class);
        $provider->expects($this->once())->method('supportPagination')->willReturn(true);
        $provider->expects($this->once())->method('getPagination')->willReturn($pagination);

        /** @var ResponseInterface|MockObject $response */
        $response = $this->createMock(ResponseInterface::class);

        /** @var ResponseDefinition|MockObject $responseDefinition */
        $responseDefinition = $this->getMockBuilder(ResponseDefinition::class)->disableOriginalConstructor()->getMock();

        $provider = new PaginationProviderChain([$provider]);
        $this->assertTrue($provider->supportPagination([], $response, $responseDefinition));
        $provider->getPagination($data, $response, $responseDefinition);
    }
}
