<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Factory\Pagination;

use TwentytwoLabs\ApiServiceBundle\Pagination\HalPagination;
use TwentytwoLabs\ApiServiceBundle\Pagination\PaginationInterface;

final class HalPaginationFactory implements PaginationFactoryInterface
{
    public function createPagination(string $name, array $options = []): PaginationInterface
    {
        return new HalPagination();
    }
}
