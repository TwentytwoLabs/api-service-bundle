<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Pagination;

use Psr\Http\Message\ResponseInterface;
use TwentytwoLabs\ApiServiceBundle\Model\Pagination;

interface PaginationInterface
{
    public const TOTAL_ITEMS = 'totalItems';
    public const TOTAL_PAGES = 'totalPages';
    public const PAGE = 'page';
    public const PER_PAGE = 'perPage';

    public function support(ResponseInterface $response): bool;

    /**
     * @param array<int|string, mixed> $data
     */
    public function getPagination(array $data, ResponseInterface $response): Pagination;
}
