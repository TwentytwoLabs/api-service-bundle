<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Tests\DataTransformer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TwentytwoLabs\ApiServiceBundle\DataTransformer\HalDataTransformer;

final class HalDataTransformerTest extends TestCase
{
    #[DataProvider('getValidContentType')]
    public function testShouldSupport(string $contentType): void
    {
        $dataTransformer = $this->getDataTransformer();
        $this->assertTrue($dataTransformer->support($contentType));
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function getValidContentType(): array
    {
        return [
            ['application/hal+json'],
            ['application/hal+json; charset=utf-8'],
        ];
    }

    #[DataProvider('getInvalidContentType')]
    public function testShouldNotSupport(string $contentType): void
    {
        $dataTransformer = $this->getDataTransformer();
        $this->assertFalse($dataTransformer->support($contentType));
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function getInvalidContentType(): array
    {
        return [
            ['application/json'],
            ['application/json; charset=utf-8'],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $expected
     * @param array<string, mixed> $data
     */
    #[DataProvider('getData')]
    public function testShouldTransform(array $expected, array $data): void
    {
        $dataTransformer = $this->getDataTransformer();
        $this->assertSame($expected, $dataTransformer->transform($data));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function getData(): array
    {
        return [
            'empty-data' => [
                [],
                [],
            ],
            'without-links' => [
                [
                    [
                        'name' => 'Login SocialNetwork',
                        'enabled' => true,
                        'expression' => '',
                        'createdBy' => 'john.doe@example.org',
                        'updatedBy' => 'jane.doe@example.org',
                        'dateCreated' => '2023-11-30T23:08:00+01:00',
                        'dateModified' => '2023-11-30T23:08:00+01:00',
                    ],
                    [
                        'name' => 'Register',
                        'enabled' => true,
                        'expression' => '',
                        'createdBy' => 'john.doe@example.org',
                        'updatedBy' => 'jane.doe@example.org',
                        'dateCreated' => '2023-11-30T23:08:00+01:00',
                        'dateModified' => '2023-11-30T23:08:00+01:00',
                    ],
                    [
                        'name' => 'Test',
                        'enabled' => false,
                        'expression' => '',
                        'createdBy' => 'john.doe@example.org',
                        'updatedBy' => 'jane.doe@example.org',
                        'dateCreated' => '2023-11-30T23:08:00+01:00',
                        'dateModified' => '2023-11-30T23:08:00+01:00',
                    ],
                ],
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
            ],
            'without-links-2' => [
                [
                    [
                        'name' => 'Login SocialNetwork',
                        'enabled' => true,
                        'expression' => '',
                        'createdBy' => 'john.doe@example.org',
                        'updatedBy' => 'jane.doe@example.org',
                        'dateCreated' => '2023-11-30T23:08:00+01:00',
                        'dateModified' => '2023-11-30T23:08:00+01:00',
                    ],
                    [
                        'name' => 'Register',
                        'enabled' => true,
                        'expression' => '',
                        'createdBy' => 'john.doe@example.org',
                        'updatedBy' => 'jane.doe@example.org',
                        'dateCreated' => '2023-11-30T23:08:00+01:00',
                        'dateModified' => '2023-11-30T23:08:00+01:00',
                    ],
                    [
                        'name' => 'Test',
                        'enabled' => false,
                        'expression' => '',
                        'createdBy' => 'john.doe@example.org',
                        'updatedBy' => 'jane.doe@example.org',
                        'dateCreated' => '2023-11-30T23:08:00+01:00',
                        'dateModified' => '2023-11-30T23:08:00+01:00',
                    ],
                ],
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
            ],
            'full-pagination' => [
                [
                    [
                        'thumbnail' => [
                            'title' => 'Alexandre Fouquet\'s avatar',
                            'uuid' => 'QWxleGFuZHJlIEZvdXF1ZXQncyBhdmF0YXI=',
                            'type' => 'avatar',
                            'src' => '075ed9a2-4764-39a2-8ca0-29a74d2d217d.png',
                            'alternativeText' => null,
                            'status' => 'available',
                            'dateCreated' => '2023-12-01T01:05:24+01:00',
                            'dateModified' => '2023-12-01T01:05:24+01:00',
                            'link' => ['url' => 'http://example.org'],
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
                        'thumbnail' => [
                            'title' => 'Nicolas Jacques\'s avatar',
                            'uuid' => 'Tmljb2xhcyBKYWNxdWVzJ3MgYXZhdGFy',
                            'type' => 'avatar',
                            'src' => 'f606fed7-de76-3d0a-8ead-74b5d8f4f4e9.png',
                            'alternativeText' => null,
                            'status' => 'available',
                            'dateCreated' => '2023-12-01T01:05:24+01:00',
                            'dateModified' => '2023-12-01T01:05:24+01:00',
                            'link' => ['url' => 'http://example.org'],
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
                        'thumbnail' => [
                            'title' => 'Augustin Hernandez\'s avatar',
                            'uuid' => 'QXVndXN0aW4gSGVybmFuZGV6J3MgYXZhdGFy',
                            'type' => 'avatar',
                            'src' => 'e2c06f4f-03d5-3ed5-8116-632ef1201401.png',
                            'alternativeText' => null,
                            'status' => 'available',
                            'dateCreated' => '2023-12-01T01:05:24+01:00',
                            'dateModified' => '2023-12-01T01:05:24+01:00',
                            'link' => ['url' => 'http://example.org'],
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
            ],
        ];
    }

    private function getDataTransformer(): HalDataTransformer
    {
        return new HalDataTransformer();
    }
}
