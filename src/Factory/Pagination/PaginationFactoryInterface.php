<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Factory\Pagination;

use TwentytwoLabs\ApiServiceBundle\Pagination\PaginationInterface;

interface PaginationFactoryInterface
{
    /**
     * @param array<int|string, mixed> $options
     */
    public function createPagination(string $name, array $options = []): PaginationInterface;
}
