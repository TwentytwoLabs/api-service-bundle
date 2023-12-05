<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Pagination;

use Psr\Http\Message\ResponseInterface;
use TwentytwoLabs\ApiServiceBundle\Model\Pagination;
use TwentytwoLabs\ApiServiceBundle\Model\PaginationLinks;

class HalPagination implements PaginationInterface
{
    public const DEFAULT_PAGINATION_VALUE = [
        self::TOTAL_PAGES => '_links.last.href.page',
        self::TOTAL_ITEMS => 'totalItems',
        self::PAGE => '_links.self.href.page',
        self::PER_PAGE => 'itemsPerPage',
    ];

    private array $paginationName;

    public function __construct()
    {
        foreach (self::DEFAULT_PAGINATION_VALUE as $name => $value) {
            $this->paginationName[$name] = explode('.', $value);
        }
    }

    public function support(ResponseInterface $response): bool
    {
        return 1 === preg_match('#hal#', $response->getHeaderLine('Content-Type'));
    }

    public function getPagination(array $data, ResponseInterface $response): Pagination
    {
        $links = $data['_links'] ?? [];
        $paginationLinks = new PaginationLinks(
            $links['first']['href'] ?? $links['self']['href'] ?? '',
            $links['last']['href'] ?? $links['self']['href'] ?? '',
            $links['next']['href'] ?? null,
            $links['prev']['href'] ?? null
        );
        $pagination = [
            self::PAGE => $this->getValue($data, $this->paginationName[self::PAGE]) ?? 1,
            self::PER_PAGE => $this->getValue($data, $this->paginationName[self::PER_PAGE]) ?? 0,
            self::TOTAL_ITEMS => $this->getValue($data, $this->paginationName[self::TOTAL_ITEMS]) ?? 0,
            self::TOTAL_PAGES => $this->getValue($data, $this->paginationName[self::TOTAL_PAGES]) ?? 1,
        ];

        return new Pagination(
            page: $pagination[self::PAGE],
            perPage: $pagination[self::PER_PAGE],
            totalItems: $pagination[self::TOTAL_ITEMS],
            totalPages: $pagination[self::TOTAL_PAGES],
            links: $paginationLinks
        );
    }

    private function getValue(array $items, array $values)
    {
        $value = $items;
        foreach ($values as $item) {
            if (isset($value[$item])) {
                $value = $value[$item];
            } elseif (is_string($value)) {
                if (1 === preg_match('#'.$item.'=([^&]*)#', $value, $tab)) {
                    $value = (int) $tab[1];
                } else {
                    return 1;
                }
            } else {
                return null;
            }
        }

        return $value;
    }
}
