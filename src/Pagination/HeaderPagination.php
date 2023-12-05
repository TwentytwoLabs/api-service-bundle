<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Pagination;

use Psr\Http\Message\ResponseInterface;
use TwentytwoLabs\ApiServiceBundle\Model\Pagination;
use TwentytwoLabs\ApiServiceBundle\Model\PaginationLinks;

class HeaderPagination implements PaginationInterface
{
    private array $paginationHeaders;

    public function __construct(array $configs = [])
    {
        $this->paginationHeaders = $configs;
    }

    public function support(ResponseInterface $response): bool
    {
        foreach ($this->paginationHeaders as $headerName) {
            if (empty($response->getHeaderLine($headerName))) {
                return false;
            }
        }

        return true;
    }

    public function getPagination(array $data, ResponseInterface $response): Pagination
    {
        $paginationLinks = null;
        if ($response->hasHeader('Link')) {
            $links = self::parseHeaderLinks($response->getHeader('Link'));
            $paginationLinks = new PaginationLinks(
                $links['first'],
                $links['last'],
                $links['next'],
                $links['prev']
            );
        }

        return new Pagination(
            (int) $response->getHeaderLine($this->paginationHeaders[self::PAGE]),
            (int) $response->getHeaderLine($this->paginationHeaders[self::PER_PAGE]),
            (int) $response->getHeaderLine($this->paginationHeaders[self::TOTAL_ITEMS]),
            (int) $response->getHeaderLine($this->paginationHeaders[self::TOTAL_PAGES]),
            $paginationLinks
        );
    }

    private static function parseHeaderLinks(array $headerLinks): array
    {
        $links = ['first' => '', 'last' => '', 'next' => null, 'prev' => null];

        foreach ($headerLinks as $headerLink) {
            if (1 === preg_match('/rel="([^"]+)"/', $headerLink, $matches)) {
                if (\in_array($matches[1] ?? '', ['next', 'prev', 'first', 'last'])) {
                    $parts = explode(';', $headerLink);
                    $links[$matches[1]] = trim($parts[0], ' <>');
                }
            }
        }

        return $links;
    }
}
