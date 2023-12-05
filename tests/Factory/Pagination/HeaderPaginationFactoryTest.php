<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Tests\Factory\Pagination;

use PHPUnit\Framework\TestCase;
use TwentytwoLabs\ApiServiceBundle\Factory\Pagination\HeaderPaginationFactory;
use TwentytwoLabs\ApiServiceBundle\Pagination\HeaderPagination;
use TwentytwoLabs\ApiServiceBundle\Pagination\PaginationInterface;

final class HeaderPaginationFactoryTest extends TestCase
{
    public function testShouldCreatePaginationWhenMissingConfigs(): void
    {
        $configs = [
            PaginationInterface::TOTAL_PAGES => 'X-Total-Items',
            PaginationInterface::PAGE => 'X-Page',
            PaginationInterface::PER_PAGE => 'X-Per-Page',
        ];

        $factory = $this->getFactory();
        $pagination = $factory->createPagination('foo', $configs);
        $this->assertInstanceOf(HeaderPagination::class, $pagination);
    }

    public function testShouldCreatePaginationWhenMissingConfigs2(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Error while configure pagination foo. Verify your configuration at "twenty-two-labs.api_service.foo.pagination.options". The option "foo" does not exist. Defined options are: "page", "perPage", "totalItems", "totalPages".');

        $configs = [
            PaginationInterface::TOTAL_PAGES => 'X-Total-Items',
            PaginationInterface::TOTAL_ITEMS => 'X-Total-Pages',
            PaginationInterface::PAGE => 'X-Page',
            PaginationInterface::PER_PAGE => 'X-Per-Page',
            'foo' => 'bar',
        ];

        $factory = $this->getFactory();
        $pagination = $factory->createPagination('foo', $configs);
        $this->assertInstanceOf(HeaderPagination::class, $pagination);
    }

    public function testShouldCreatePaginationWithDefaultValue(): void
    {
        $factory = $this->getFactory();
        $pagination = $factory->createPagination('foo');
        $this->assertInstanceOf(HeaderPagination::class, $pagination);
    }

    private function getFactory(): HeaderPaginationFactory
    {
        return new HeaderPaginationFactory();
    }
}
