<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Tests\Factory\Pagination;

use PHPUnit\Framework\TestCase;
use TwentytwoLabs\ApiServiceBundle\Factory\Pagination\HalPaginationFactory;
use TwentytwoLabs\ApiServiceBundle\Pagination\HalPagination;

final class HalPaginationFactoryTest extends TestCase
{
    public function testShouldCreatePagination(): void
    {
        $factory = $this->getFactory();
        $pagination = $factory->createPagination('foo');
        $this->assertInstanceOf(HalPagination::class, $pagination);
    }

    private function getFactory(): HalPaginationFactory
    {
        return new HalPaginationFactory();
    }
}
