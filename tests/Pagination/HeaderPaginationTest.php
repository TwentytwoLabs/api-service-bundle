<?php

declare(strict_types=1);

namespace Pagination;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use TwentytwoLabs\ApiServiceBundle\Model\Pagination;
use TwentytwoLabs\ApiServiceBundle\Model\PaginationLinks;
use TwentytwoLabs\ApiServiceBundle\Pagination\HeaderPagination;
use TwentytwoLabs\ApiServiceBundle\Pagination\PaginationInterface;

final class HeaderPaginationTest extends TestCase
{
    public function testShouldSupportPagination(): void
    {
        $matcher = $this->exactly(4);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($matcher)
            ->method('getHeaderLine')
            ->willReturnCallback(function (string $name) use ($matcher) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertEquals('x-page', $name),
                    2 => $this->assertEquals('x-per-page', $name),
                    3 => $this->assertEquals('x-total-items', $name),
                    4 => $this->assertEquals('x-total-pages', $name),
                };

                return match ($matcher->numberOfInvocations()) {
                    1 => '1',
                    2 => '30',
                    3 => '300',
                    4 => '10',
                };
            })
        ;

        $configs = [
            'page' => 'x-page',
            'perPage' => 'x-per-page',
            'totalItems' => 'x-total-items',
            'totalPages' => 'x-total-pages',
        ];

        $paginator = $this->getPagination($configs);
        $this->assertTrue($paginator->support($response));
    }

    public function testShouldNotSupportPagination(): void
    {
        $matcher = $this->exactly(2);

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($matcher)
            ->method('getHeaderLine')
            ->willReturnCallback(function (string $name) use ($matcher) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertEquals('x-page', $name),
                    2 => $this->assertEquals('x-per-page', $name),
                };

                return match ($matcher->numberOfInvocations()) {
                    1 => '1',
                    2 => '',
                };
            })
        ;

        $configs = [
            'page' => 'x-page',
            'perPage' => 'x-per-page',
            'totalItems' => 'x-total-items',
            'totalPages' => 'x-total-pages',
        ];

        $paginator = $this->getPagination($configs);
        $this->assertFalse($paginator->support($response));
    }

    public function testShouldProvidePaginationWithoutLinks(): void
    {
        $matcher = $this->exactly(4);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('hasHeader')->with('Link')->willReturn(true);
        $response
            ->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([])
        ;
        $response
            ->expects($matcher)
            ->method('getHeaderLine')
            ->willReturnCallback(function (string $name) use ($matcher) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertEquals('x-page', $name),
                    2 => $this->assertEquals('x-per-page', $name),
                    3 => $this->assertEquals('x-total-items', $name),
                    4 => $this->assertEquals('x-total-pages', $name),
                };

                return match ($matcher->numberOfInvocations()) {
                    1 => '3',
                    2 => '30',
                    3 => '300',
                    4 => '10',
                };
            })
        ;

        $configs = [
            'page' => 'x-page',
            'perPage' => 'x-per-page',
            'totalItems' => 'x-total-items',
            'totalPages' => 'x-total-pages',
        ];

        $paginator = $this->getPagination($configs);
        $pagination = $paginator->getPagination([], $response);

        $this->assertInstanceOf(Pagination::class, $pagination);

        $this->assertTrue($pagination->hasLinks());
        $link = $pagination->getLinks();
        $this->assertInstanceOf(PaginationLinks::class, $link);
        $this->assertSame('', $link->getFirst());
        $this->assertNull($link->getPrev());
        $this->assertNull($link->getNext());
        $this->assertFalse($link->hasPrev());
        $this->assertFalse($link->hasNext());
        $this->assertSame('', $link->getLast());

        $this->assertSame(3, $pagination->getPage());
        $this->assertSame(30, $pagination->getPerPage());
        $this->assertSame(300, $pagination->getTotalItems());
        $this->assertSame(10, $pagination->getTotalPages());
    }

    public function testShouldProvidePagination(): void
    {
        $matcher = $this->exactly(4);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('hasHeader')->with('Link')->willReturn(true);
        $response
            ->expects($this->once())
            ->method('getHeader')
            ->with('Link')
            ->willReturn([
                '<https://api.github.com/repositories/1300192/issues?page=2>; rel="prev"',
                '<https://api.github.com/repositories/1300192/issues?page=4>; rel="next"',
                '<https://api.github.com/repositories/1300192/issues?page=10>; rel="last"',
                '<https://api.github.com/repositories/1300192/issues?page=1>; rel="first"',
            ])
        ;
        $response
            ->expects($matcher)
            ->method('getHeaderLine')
            ->willReturnCallback(function (string $name) use ($matcher) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertEquals('X-Page', $name),
                    2 => $this->assertEquals('X-Per-Page', $name),
                    3 => $this->assertEquals('X-Total-Items', $name),
                    4 => $this->assertEquals('X-Total-Pages', $name),
                };

                return match ($matcher->numberOfInvocations()) {
                    1 => '3',
                    2 => '30',
                    3 => '300',
                    4 => '10',
                };
            })
        ;

        $configs = [
            PaginationInterface::TOTAL_PAGES => 'X-Total-Pages',
            PaginationInterface::TOTAL_ITEMS => 'X-Total-Items',
            PaginationInterface::PAGE => 'X-Page',
            PaginationInterface::PER_PAGE => 'X-Per-Page',
        ];

        $paginator = $this->getPagination($configs);
        $pagination = $paginator->getPagination([], $response);

        $this->assertInstanceOf(Pagination::class, $pagination);

        $this->assertTrue($pagination->hasLinks());
        $link = $pagination->getLinks();
        $this->assertInstanceOf(PaginationLinks::class, $link);
        $this->assertSame('https://api.github.com/repositories/1300192/issues?page=1', $link->getFirst());
        $this->assertSame('https://api.github.com/repositories/1300192/issues?page=2', $link->getPrev());
        $this->assertSame('https://api.github.com/repositories/1300192/issues?page=4', $link->getNext());
        $this->assertTrue($link->hasPrev());
        $this->assertTrue($link->hasNext());
        $this->assertSame('https://api.github.com/repositories/1300192/issues?page=10', $link->getLast());

        $this->assertSame(3, $pagination->getPage());
        $this->assertSame(30, $pagination->getPerPage());
        $this->assertSame(300, $pagination->getTotalItems());
        $this->assertSame(10, $pagination->getTotalPages());
    }

    private function getPagination(array $configs = []): HeaderPagination
    {
        return new HeaderPagination($configs);
    }
}
