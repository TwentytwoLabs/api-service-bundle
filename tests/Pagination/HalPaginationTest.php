<?php

declare(strict_types=1);

namespace Pagination;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use TwentytwoLabs\ApiServiceBundle\Model\Pagination;
use TwentytwoLabs\ApiServiceBundle\Pagination\HalPagination;

final class HalPaginationTest extends TestCase
{
    #[DataProvider('getContentType')]
    public function testShouldSupportPagination(bool $expected, string $contentType): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getHeaderLine')->with('Content-Type')->willReturn($contentType);

        $pagination = $this->getPagination();
        $this->assertSame($expected, $pagination->support($response));
    }

    public static function getContentType(): array
    {
        return [
            [true, 'hal'],
            [true, 'application/hal+json'],
            [true, 'application/hal+json; charset=utf-8'],

            [false, ''],
            [false, 'application/json'],
            [false, 'application/json; charset=utf-8'],

            [false, 'application/ld+json'],
            [false, 'application/ld+json; charset=utf-8'],
        ];
    }

    #[DataProvider('getDataPagination')]
    public function testShouldProvidePagination(array $data, array $paginationData, array $linksData): void
    {
        $response = $this->createMock(ResponseInterface::class);

        $paginator = $this->getPagination();
        $pagination = $paginator->getPagination($data, $response);

        $this->assertInstanceOf(Pagination::class, $pagination);
        $this->assertSame($paginationData['page'], $pagination->getPage());
        $this->assertSame($paginationData['perPage'], $pagination->getPerPage());
        $this->assertSame($paginationData['totalPage'], $pagination->getTotalPages());
        $this->assertSame($paginationData['totalItems'], $pagination->getTotalItems());

        $this->assertTrue($pagination->hasLinks());

        $links = $pagination->getLinks();
        $this->assertSame($linksData['first'], $links->getFirst());

        $this->assertSame(!empty($linksData['next']), $links->hasNext());
        $this->assertSame($linksData['next'], $links->getNext());

        $this->assertSame(!empty($linksData['prev']), $links->hasPrev());
        $this->assertSame($linksData['prev'], $links->getPrev());

        $this->assertSame($linksData['last'], $links->getLast());
    }

    public static function getDataPagination(): array
    {
        return [
            'empty-data' => [
                [],
                ['page' => 1, 'perPage' => 0, 'totalPage' => 1, 'totalItems' => 0],
                ['first' => '', 'next' => null, 'prev' => null, 'last' => ''],
            ],
            'without-links' => [
                [
                    'totalItems' => 53,
                    'itemsPerPage' => 3,
                    '_embedded' => [
                        'item' => [
                            [
                                '_links' => [
                                    'self' => [
                                        'href' => '/features/1',
                                    ],
                                ],
                                'name' => 'Login SocialNetwork',
                                'enabled' => true,
                                'expression' => '',
                                'createdBy' => 'john.doe@example.org',
                                'updatedBy' => 'jane.doe@example.org',
                                'dateCreated' => '2023-11-30T23:08:00+01:00',
                                'dateModified' => '2023-11-30T23:08:00+01:00',
                            ],
                            [
                                '_links' => [
                                    'self' => [
                                        'href' => '/features/2',
                                    ],
                                ],
                                'name' => 'Register',
                                'enabled' => true,
                                'expression' => '',
                                'createdBy' => 'john.doe@example.org',
                                'updatedBy' => 'jane.doe@example.org',
                                'dateCreated' => '2023-11-30T23:08:00+01:00',
                                'dateModified' => '2023-11-30T23:08:00+01:00',
                            ],
                            [
                                '_links' => [
                                    'self' => [
                                        'href' => '/features/3',
                                    ],
                                ],
                                'name' => 'Test',
                                'enabled' => false,
                                'expression' => '',
                                'createdBy' => 'john.doe@example.org',
                                'updatedBy' => 'jane.doe@example.org',
                                'dateCreated' => '2023-11-30T23:08:00+01:00',
                                'dateModified' => '2023-11-30T23:08:00+01:00',
                            ],
                        ],
                    ],
                ],
                ['page' => 1, 'perPage' => 3, 'totalPage' => 1, 'totalItems' => 53],
                ['first' => '', 'next' => null, 'prev' => null, 'last' => ''],
            ],
            'without-links-2' => [
                [
                    'totalItems' => 3,
                    'itemsPerPage' => 30,
                    '_embedded' => [
                        'item' => [
                            [
                                '_links' => [
                                    'self' => [
                                        'href' => '/features/1',
                                    ],
                                ],
                                'name' => 'Login SocialNetwork',
                                'enabled' => true,
                                'expression' => '',
                                'createdBy' => 'john.doe@example.org',
                                'updatedBy' => 'jane.doe@example.org',
                                'dateCreated' => '2023-11-30T23:08:00+01:00',
                                'dateModified' => '2023-11-30T23:08:00+01:00',
                            ],
                            [
                                '_links' => [
                                    'self' => [
                                        'href' => '/features/2',
                                    ],
                                ],
                                'name' => 'Register',
                                'enabled' => true,
                                'expression' => '',
                                'createdBy' => 'john.doe@example.org',
                                'updatedBy' => 'jane.doe@example.org',
                                'dateCreated' => '2023-11-30T23:08:00+01:00',
                                'dateModified' => '2023-11-30T23:08:00+01:00',
                            ],
                            [
                                '_links' => [
                                    'self' => [
                                        'href' => '/features/3',
                                    ],
                                ],
                                'name' => 'Test',
                                'enabled' => false,
                                'expression' => '',
                                'createdBy' => 'john.doe@example.org',
                                'updatedBy' => 'jane.doe@example.org',
                                'dateCreated' => '2023-11-30T23:08:00+01:00',
                                'dateModified' => '2023-11-30T23:08:00+01:00',
                            ],
                        ],
                    ],
                ],
                ['page' => 1, 'perPage' => 30, 'totalPage' => 1, 'totalItems' => 3],
                ['first' => '', 'next' => null, 'prev' => null, 'last' => ''],
            ],
            'full-pagination' => [
                [
                    '_links' => [
                        'self' => ['href' => '/features?itemsPerPage=3&page=2'],
                        'first' => ['href' => '/features?itemsPerPage=3&page=1'],
                        'last' => ['href' => '/features?itemsPerPage=3&page=18'],
                        'prev' => ['href' => '/features?itemsPerPage=3&page=1'],
                        'next' => ['href' => '/features?itemsPerPage=3&page=3'],
                        'item' => [
                            ['href' => '/features/4'],
                            ['href' => '/features/5'],
                            ['href' => '/features/6'],
                        ],
                    ],
                    'totalItems' => 52,
                    'itemsPerPage' => 3,
                    '_embedded' => [
                        'item' => [
                            [
                                '_links' => ['self' => ['href' => '/features/4']],
                                '_embedded' => [
                                    'thumbnail' => [
                                        '_links' => [
                                            'self' => ['href' => '/images/QWxleGFuZHJlIEZvdXF1ZXQncyBhdmF0YXI='],
                                        ],
                                        '_embedded' => [
                                            'link' => [
                                                'url' => 'http://example.org',
                                            ],
                                        ],
                                        'title' => 'Alexandre Fouquet\'s avatar',
                                        'uuid' => 'QWxleGFuZHJlIEZvdXF1ZXQncyBhdmF0YXI=',
                                        'type' => 'avatar',
                                        'src' => '075ed9a2-4764-39a2-8ca0-29a74d2d217d.png',
                                        'alternativeText' => null,
                                        'status' => 'available',
                                        'dateCreated' => '2023-12-01T01:05:24+01:00',
                                        'dateModified' => '2023-12-01T01:05:24+01:00',
                                    ],
                                ],
                                'name' => 'non',
                                'enabled' => true,
                                'expression' => '',
                                'createdBy' => 'john.doe@example.org',
                                'updatedBy' => 'jane.doe@example.org',
                                'dateCreated' => '2023-11-30T23:08:00+01:00',
                                'dateModified' => '2023-11-30T23:08:00+01:00',
                            ],
                            [
                                '_links' => ['self' => ['href' => '/features/5']],
                                '_embedded' => [
                                    'thumbnail' => [
                                        '_links' => [
                                            'self' => ['href' => '/images/Tmljb2xhcyBKYWNxdWVzJ3MgYXZhdGFy'],
                                        ],
                                        '_embedded' => [
                                            'link' => ['url' => 'http://example.org'],
                                        ],
                                        'title' => 'Nicolas Jacques\'s avatar',
                                        'uuid' => 'Tmljb2xhcyBKYWNxdWVzJ3MgYXZhdGFy',
                                        'type' => 'avatar',
                                        'src' => 'f606fed7-de76-3d0a-8ead-74b5d8f4f4e9.png',
                                        'alternativeText' => null,
                                        'status' => 'available',
                                        'dateCreated' => '2023-12-01T01:05:24+01:00',
                                        'dateModified' => '2023-12-01T01:05:24+01:00',
                                    ],
                                ],
                                'name' => 'ad',
                                'enabled' => false,
                                'expression' => '',
                                'createdBy' => 'john.doe@example.org',
                                'updatedBy' => 'jane.doe@example.org',
                                'dateCreated' => '2023-11-30T23:08:00+01:00',
                                'dateModified' => '2023-11-30T23:08:00+01:00',
                            ],
                            [
                                '_links' => [
                                    'self' => ['href' => '/features/6'],
                                ],
                                '_embedded' => [
                                    'thumbnail' => [
                                        '_links' => [
                                            'self' => ['href' => '/images/QXVndXN0aW4gSGVybmFuZGV6J3MgYXZhdGFy'],
                                        ],
                                        '_embedded' => [
                                            'link' => ['url' => 'http://example.org'],
                                        ],
                                        'title' => 'Augustin Hernandez\'s avatar',
                                        'uuid' => 'QXVndXN0aW4gSGVybmFuZGV6J3MgYXZhdGFy',
                                        'type' => 'avatar',
                                        'src' => 'e2c06f4f-03d5-3ed5-8116-632ef1201401.png',
                                        'alternativeText' => null,
                                        'status' => 'available',
                                        'dateCreated' => '2023-12-01T01:05:24+01:00',
                                        'dateModified' => '2023-12-01T01:05:24+01:00',
                                    ],
                                ],
                                'name' => 'omnis',
                                'enabled' => false,
                                'expression' => '',
                                'createdBy' => 'jane.doe@example.org',
                                'updatedBy' => 'john.doe@example.org',
                                'dateCreated' => '2023-11-30T23:08:00+01:00',
                                'dateModified' => '2023-11-30T23:08:00+01:00',
                            ],
                        ],
                    ],
                ],
                ['page' => 2, 'perPage' => 3, 'totalPage' => 18, 'totalItems' => 52],
                [
                    'first' => '/features?itemsPerPage=3&page=1',
                    'next' => '/features?itemsPerPage=3&page=3',
                    'prev' => '/features?itemsPerPage=3&page=1',
                    'last' => '/features?itemsPerPage=3&page=18',
                ],
            ],
            'empty-pagination' => [
                [
                    '_links' => [
                        'self' => [
                            'href' => '/features',
                        ],
                    ],
                    'totalItems' => 0,
                    'itemsPerPage' => 30,
                ],
                ['page' => 1, 'perPage' => 30, 'totalPage' => 1, 'totalItems' => 0],
                ['first' => '/features', 'next' => null, 'prev' => null, 'last' => '/features'],
            ],
            'empty-pagination-and-empty-data' => [
                [
                    'totalItems' => 0,
                    'itemsPerPage' => 30,
                ],
                ['page' => 1, 'perPage' => 30, 'totalPage' => 1, 'totalItems' => 0],
                ['first' => '', 'next' => null, 'prev' => null, 'last' => ''],
            ],
            'last-page' => [
                [
                    '_links' => [
                        'self' => ['href' => '/features?itemsPerPage=3'],
                        'item' => [
                            ['href' => '/features/1'],
                            ['href' => '/features/2'],
                            ['href' => '/features/3'],
                        ],
                    ],
                    'totalItems' => 3,
                    'itemsPerPage' => 3,
                    '_embedded' => [
                        'item' => [
                            [
                                '_links' => ['self' => ['href' => '/features/1']],
                                'name' => 'Login SocialNetwork',
                                'enabled' => true,
                                'expression' => '',
                                'createdBy' => 'john.doe@example.org',
                                'updatedBy' => 'jane.doe@example.org',
                                'dateCreated' => '2023-11-30T23:08:00+01:00',
                                'dateModified' => '2023-11-30T23:08:00+01:00',
                            ],
                            [
                                '_links' => ['self' => ['href' => '/features/2']],
                                'name' => 'Register',
                                'enabled' => true,
                                'expression' => '',
                                'createdBy' => 'john.doe@example.org',
                                'updatedBy' => 'jane.doe@example.org',
                                'dateCreated' => '2023-11-30T23:08:00+01:00',
                                'dateModified' => '2023-11-30T23:08:00+01:00',
                            ],
                            [
                                '_links' => ['self' => ['href' => '/features/3']],
                                'name' => 'Test',
                                'enabled' => false,
                                'expression' => '',
                                'createdBy' => 'john.doe@example.org',
                                'updatedBy' => 'jane.doe@example.org',
                                'dateCreated' => '2023-11-30T23:08:00+01:00',
                                'dateModified' => '2023-11-30T23:08:00+01:00',
                            ],
                        ],
                    ],
                ],
                ['page' => 1, 'perPage' => 3, 'totalPage' => 1, 'totalItems' => 3],
                [
                    'first' => '/features?itemsPerPage=3',
                    'next' => null,
                    'prev' => null,
                    'last' => '/features?itemsPerPage=3',
                ],
            ],
        ];
    }

    private function getPagination(): HalPagination
    {
        return new HalPagination();
    }
}
