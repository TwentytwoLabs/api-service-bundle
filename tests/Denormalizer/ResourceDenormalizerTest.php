<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Tests\Denormalizer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use TwentytwoLabs\ApiServiceBundle\Model\ErrorInterface;
use TwentytwoLabs\ApiValidator\Definition\ResponseDefinition;
use TwentytwoLabs\ApiServiceBundle\DataTransformer\DataTransformer;
use TwentytwoLabs\ApiServiceBundle\Denormalizer\ErrorDenormalizer;
use TwentytwoLabs\ApiServiceBundle\Denormalizer\ResourceDenormalizer;
use TwentytwoLabs\ApiServiceBundle\Model\Collection;
use TwentytwoLabs\ApiServiceBundle\Model\Item;
use TwentytwoLabs\ApiServiceBundle\Model\Pagination;
use TwentytwoLabs\ApiServiceBundle\Model\ResourceInterface;
use TwentytwoLabs\ApiServiceBundle\Pagination\PaginationInterface;

final class ResourceDenormalizerTest extends TestCase
{
    private DataTransformer|MockObject $dataTransformer;

    protected function setUp(): void
    {
        $this->dataTransformer = $this->createMock(DataTransformer::class);
    }

    public function testShouldSupportResourceType(): void
    {
        $denormalizer = $this->getDenormalizer();
        $this->assertTrue($denormalizer->supportsDenormalization([], ResourceInterface::class));
    }

    public function testShouldNotSupportResourceType(): void
    {
        $denormalizer = $this->getDenormalizer();
        $this->assertFalse($denormalizer->supportsDenormalization([], ErrorDenormalizer::class));
    }

    public function testShouldNotProvideAResourceBecauseResponseHasNotBodySchema(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot transform the response into a resource. You need to provide a schema for response 200 in GET /foo');

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->never())->method('getHeaders');
        $response->expects($this->never())->method('getHeaderLine');
        $response->expects($this->once())->method('getStatusCode')->willReturn(200);

        $uri = $this->createMock(UriInterface::class);
        $uri->expects($this->once())->method('getPath')->willReturn('/foo');

        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->once())->method('getMethod')->willReturn('GET');
        $request->expects($this->once())->method('getUri')->willReturn($uri);

        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->once())->method('hasBodySchema')->willReturn(false);
        $responseDefinition->expects($this->never())->method('getBodySchema');

        $this->dataTransformer->expects($this->never())->method('transform');

        $denormalizer = $this->getDenormalizer();
        $denormalizer->denormalize(
            data: [],
            type: ResourceInterface::class,
            context: [
                'response' => $response,
                'responseDefinition' => $responseDefinition,
                'request' => $request,
                'pagination' => null,
            ],
        );
    }

    public function testShouldNotProvideAResourceBecauseTypeIsNotRecognized(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot extract type from schema');

        $bodySchema = [];

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->never())->method('getHeaders');
        $response
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/hal+json')
        ;
        $response->expects($this->never())->method('getStatusCode');

        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->never())->method('getMethod');
        $request->expects($this->never())->method('getUri');

        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->once())->method('hasBodySchema')->willReturn(true);
        $responseDefinition->expects($this->once())->method('getBodySchema')->willReturn($bodySchema);

        $this->dataTransformer->expects($this->never())->method('transform');

        $denormalizer = $this->getDenormalizer();
        $denormalizer->denormalize(
            data: [],
            type: ResourceInterface::class,
            context: [
                'response' => $response,
                'responseDefinition' => $responseDefinition,
                'request' => $request,
                'pagination' => null,
            ],
        );
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public static function getValidBodySchemes(): array
    {
        return [
            [
                [
                    'application/json' => [
                        'schema' => [
                            'type' => 'array',
                            'x-type' => 'object',
                            'required' => ['title', 'type', 'file'],
                            'properties' => [
                                'title' => ['type' => 'string'],
                                'type' => ['type' => 'string', 'enum' => ['avatar', 'skill']],
                                'alternativeText' => ['type' => 'string'],
                                'file' => ['type' => 'string'],
                            ],
                        ],
                    ],
                    'application/hal+json' => [
                        'schema' => [
                            'type' => 'array',
                            'x-type' => 'object',
                            'required' => ['title', 'type', 'file'],
                            'properties' => [
                                'title' => ['type' => 'string'],
                                'type' => ['type' => 'string', 'enum' => ['avatar', 'skill']],
                                'alternativeText' => ['type' => 'string'],
                                'file' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'required' => ['title', 'type', 'file'],
                            'properties' => [
                                'title' => ['type' => 'string'],
                                'type' => ['type' => 'string', 'enum' => ['avatar', 'skill']],
                                'alternativeText' => ['type' => 'string'],
                                'file' => ['type' => 'string'],
                            ],
                        ],
                    ],
                    'application/hal+json' => [
                        'schema' => [
                            'type' => 'object',
                            'required' => ['title', 'type', 'file'],
                            'properties' => [
                                'title' => ['type' => 'string'],
                                'type' => ['type' => 'string', 'enum' => ['avatar', 'skill']],
                                'alternativeText' => ['type' => 'string'],
                                'file' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<int, mixed> $bodySchema
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    #[DataProvider('getValidBodySchemes')]
    public function testShouldProvideAResourceOfTypeItem(array $bodySchema): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getHeaders')
            ->willReturn(['Content-Type' => ['application/json', 'application/hal+json']])
        ;
        $response
            ->expects($this->exactly(2))
            ->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json')
        ;
        $response->expects($this->never())->method('getStatusCode');

        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->never())->method('getMethod');
        $request->expects($this->never())->method('getUri');

        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->once())->method('hasBodySchema')->willReturn(true);
        $responseDefinition->expects($this->once())->method('getBodySchema')->willReturn($bodySchema);

        $this->dataTransformer
            ->expects($this->once())
            ->method('transform')
            ->willReturn(['title' => 'bar', 'type' => 'avatar', 'alternativeText' => 'foo'])
        ;

        $denormalizer = $this->getDenormalizer();
        $resource = $denormalizer->denormalize(
            data: ['title' => 'bar', 'type' => 'avatar', 'alternativeText' => 'foo'],
            type: ResourceInterface::class,
            context: [
                'response' => $response,
                'responseDefinition' => $responseDefinition,
                'request' => $request,
                'pagination' => null,
            ]
        );

        $this->assertInstanceOf(Item::class, $resource);
        $this->assertNotInstanceOf(Collection::class, $resource);
        $this->assertSame(['title' => 'bar', 'type' => 'avatar', 'alternativeText' => 'foo'], $resource->getData());
        $this->assertSame(
            ['headers' => ['Content-Type' => ['application/json', 'application/hal+json']]],
            $resource->getMeta()
        );
    }

    public function testShouldProvideAResourceOfTypeCollectionWithoutPagination(): void
    {
        $data = [
            [
                'name' => 'foo',
                'enabled' => true,
                'expression' => '',
                'createdBy' => 'john.doe@example.org',
                'updatedBy' => 'jane.doe@example.org',
                'dateCreated' => '2023-11-24T15:39:14+01:00',
                'dateModified' => '2023-11-24T15:39:14+01:00',
            ],
            [
                'name' => 'bar',
                'enabled' => true,
                'expression' => '',
                'createdBy' => 'john.doe@example.org',
                'updatedBy' => 'jane.doe@example.org',
                'dateCreated' => '2023-11-24T15:39:14+01:00',
                'dateModified' => '2023-11-24T15:39:14+01:00',
            ],
            [
                'name' => 'baz',
                'enabled' => false,
                'expression' => '',
                'createdBy' => 'john.doe@example.org',
                'updatedBy' => 'jane.doe@example.org',
                'dateCreated' => '2023-11-24T15:39:14+01:00',
                'dateModified' => '2023-11-24T15:39:14+01:00',
            ],
        ];

        $bodySchema = [
            'application/json' => [
                'schema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'description' => '',
                        'deprecated' => false,
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                            ],
                            'enabled' => [
                                'type' => 'boolean',
                            ],
                            'expression' => [
                                'type' => 'string',
                            ],
                            'createdBy' => [
                                'type' => 'string',
                            ],
                            'updatedBy' => [
                                'type' => 'string',
                            ],
                            'dateCreated' => [
                                'readOnly' => true,
                                'type' => 'string',
                                'format' => 'date-time',
                            ],
                            'dateModified' => [
                                'readOnly' => true,
                                'type' => 'string',
                                'format' => 'date-time',
                            ],
                        ],
                    ],
                    'x-type' => 'array',
                ],
            ],
            'application/hal+json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        '_embedded' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'description' => '',
                                'deprecated' => false,
                                'properties' => [
                                    '_links' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'self' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'href' => [
                                                        'type' => 'string',
                                                        'format' => 'iri-reference',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    'name' => [
                                        'type' => 'string',
                                    ],
                                    'enabled' => [
                                        'type' => 'boolean',
                                    ],
                                    'expression' => [
                                        'type' => 'string',
                                    ],
                                    'createdBy' => [
                                        'type' => 'string',
                                    ],
                                    'updatedBy' => [
                                        'type' => 'string',
                                    ],
                                    'dateCreated' => [
                                        'readOnly' => true,
                                        'type' => 'string',
                                        'format' => 'date-time',
                                    ],
                                    'dateModified' => [
                                        'readOnly' => true,
                                        'type' => 'string',
                                        'format' => 'date-time',
                                    ],
                                ],
                            ],
                        ],
                        'totalItems' => [
                            'type' => 'integer',
                            'minimum' => 0,
                        ],
                        'itemsPerPage' => [
                            'type' => 'integer',
                            'minimum' => 0,
                        ],
                        '_links' => [
                            'type' => 'object',
                            'properties' => [
                                'self' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'href' => [
                                            'type' => 'string',
                                            'format' => 'iri-reference',
                                        ],
                                    ],
                                ],
                                'first' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'href' => [
                                            'type' => 'string',
                                            'format' => 'iri-reference',
                                        ],
                                    ],
                                ],
                                'last' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'href' => [
                                            'type' => 'string',
                                            'format' => 'iri-reference',
                                        ],
                                    ],
                                ],
                                'next' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'href' => [
                                            'type' => 'string',
                                            'format' => 'iri-reference',
                                        ],
                                    ],
                                ],
                                'previous' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'href' => [
                                            'type' => 'string',
                                            'format' => 'iri-reference',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'required' => [
                        '_links',
                        '_embedded',
                    ],
                    'x-type' => 'array',
                ],
            ],
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getHeaders')
            ->willReturn(['Content-Type' => ['application/json', 'application/hal+json']])
        ;
        $response
            ->expects($this->exactly(2))
            ->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json')
        ;
        $response->expects($this->never())->method('getStatusCode');

        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->never())->method('getMethod');
        $request->expects($this->never())->method('getUri');

        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->once())->method('hasBodySchema')->willReturn(true);
        $responseDefinition->expects($this->once())->method('getBodySchema')->willReturn($bodySchema);

        $this->dataTransformer
            ->expects($this->once())
            ->method('transform')
            ->with('application/json', $data)
            ->willReturn($data)
        ;

        $denormalizer = $this->getDenormalizer();
        $resource = $denormalizer->denormalize(
            data: $data,
            type: ResourceInterface::class,
            context: [
                'response' => $response,
                'responseDefinition' => $responseDefinition,
                'request' => $request,
                'pagination' => null,
            ],
        );

        $this->assertInstanceOf(Collection::class, $resource);
        $this->assertSame(
            [
                [
                    'name' => 'foo',
                    'enabled' => true,
                    'expression' => '',
                    'createdBy' => 'john.doe@example.org',
                    'updatedBy' => 'jane.doe@example.org',
                    'dateCreated' => '2023-11-24T15:39:14+01:00',
                    'dateModified' => '2023-11-24T15:39:14+01:00',
                ],
                [
                    'name' => 'bar',
                    'enabled' => true,
                    'expression' => '',
                    'createdBy' => 'john.doe@example.org',
                    'updatedBy' => 'jane.doe@example.org',
                    'dateCreated' => '2023-11-24T15:39:14+01:00',
                    'dateModified' => '2023-11-24T15:39:14+01:00',
                ],
                [
                    'name' => 'baz',
                    'enabled' => false,
                    'expression' => '',
                    'createdBy' => 'john.doe@example.org',
                    'updatedBy' => 'jane.doe@example.org',
                    'dateCreated' => '2023-11-24T15:39:14+01:00',
                    'dateModified' => '2023-11-24T15:39:14+01:00',
                ],
            ],
            $resource->getData()
        );
        $this->assertSame(
            ['headers' => ['Content-Type' => ['application/json', 'application/hal+json']]],
            $resource->getMeta()
        );
        $this->assertFalse($resource->hasPagination());
        $this->assertNull($resource->getPagination());
    }

    public function testShouldProvideAResourceOfTypeCollectionWithPagination(): void
    {
        $data = [
            '_links' => [
                'self' => [
                    'href' => '/features?itemsPerPage=30',
                ],
                'item' => [
                    [
                        'href' => '/features/1',
                    ],
                    [
                        'href' => '/features/2',
                    ],
                    [
                        'href' => '/features/3',
                    ],
                ],
            ],
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
                        'name' => 'foo',
                        'enabled' => true,
                        'expression' => '',
                        'createdBy' => 'john.doe@example.org',
                        'updatedBy' => 'jane.doe@example.org',
                        'dateCreated' => '2023-11-24T15:39:14+01:00',
                        'dateModified' => '2023-11-24T15:39:14+01:00',
                    ],
                    [
                        '_links' => [
                            'self' => [
                                'href' => '/features/2',
                            ],
                        ],
                        'name' => 'bar',
                        'enabled' => true,
                        'expression' => '',
                        'createdBy' => 'john.doe@example.org',
                        'updatedBy' => 'jane.doe@example.org',
                        'dateCreated' => '2023-11-24T15:39:14+01:00',
                        'dateModified' => '2023-11-24T15:39:14+01:00',
                    ],
                    [
                        '_links' => [
                            'self' => [
                                'href' => '/features/3',
                            ],
                        ],
                        'name' => 'baz',
                        'enabled' => false,
                        'expression' => '',
                        'createdBy' => 'john.doe@example.org',
                        'updatedBy' => 'jane.doe@example.org',
                        'dateCreated' => '2023-11-24T15:39:14+01:00',
                        'dateModified' => '2023-11-24T15:39:14+01:00',
                    ],
                ],
            ],
        ];

        $bodySchema = [
            'application/json' => [
                'schema' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'description' => '',
                        'deprecated' => false,
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                            ],
                            'enabled' => [
                                'type' => 'boolean',
                            ],
                            'expression' => [
                                'type' => 'string',
                            ],
                            'createdBy' => [
                                'type' => 'string',
                            ],
                            'updatedBy' => [
                                'type' => 'string',
                            ],
                            'dateCreated' => [
                                'readOnly' => true,
                                'type' => 'string',
                                'format' => 'date-time',
                            ],
                            'dateModified' => [
                                'readOnly' => true,
                                'type' => 'string',
                                'format' => 'date-time',
                            ],
                        ],
                    ],
                    'x-type' => 'array',
                ],
            ],
            'application/hal+json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        '_embedded' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'description' => '',
                                'deprecated' => false,
                                'properties' => [
                                    '_links' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'self' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'href' => [
                                                        'type' => 'string',
                                                        'format' => 'iri-reference',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    'name' => [
                                        'type' => 'string',
                                    ],
                                    'enabled' => [
                                        'type' => 'boolean',
                                    ],
                                    'expression' => [
                                        'type' => 'string',
                                    ],
                                    'createdBy' => [
                                        'type' => 'string',
                                    ],
                                    'updatedBy' => [
                                        'type' => 'string',
                                    ],
                                    'dateCreated' => [
                                        'readOnly' => true,
                                        'type' => 'string',
                                        'format' => 'date-time',
                                    ],
                                    'dateModified' => [
                                        'readOnly' => true,
                                        'type' => 'string',
                                        'format' => 'date-time',
                                    ],
                                ],
                            ],
                        ],
                        'totalItems' => [
                            'type' => 'integer',
                            'minimum' => 0,
                        ],
                        'itemsPerPage' => [
                            'type' => 'integer',
                            'minimum' => 0,
                        ],
                        '_links' => [
                            'type' => 'object',
                            'properties' => [
                                'self' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'href' => [
                                            'type' => 'string',
                                            'format' => 'iri-reference',
                                        ],
                                    ],
                                ],
                                'first' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'href' => [
                                            'type' => 'string',
                                            'format' => 'iri-reference',
                                        ],
                                    ],
                                ],
                                'last' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'href' => [
                                            'type' => 'string',
                                            'format' => 'iri-reference',
                                        ],
                                    ],
                                ],
                                'next' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'href' => [
                                            'type' => 'string',
                                            'format' => 'iri-reference',
                                        ],
                                    ],
                                ],
                                'previous' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'href' => [
                                            'type' => 'string',
                                            'format' => 'iri-reference',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'required' => [
                        '_links',
                        '_embedded',
                    ],
                    'x-type' => 'array',
                ],
            ],
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->once())
            ->method('getHeaders')
            ->willReturn(['Content-Type' => ['application/json', 'application/hal+json']])
        ;
        $response
            ->expects($this->exactly(2))
            ->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/hal+json')
        ;
        $response->expects($this->never())->method('getStatusCode');

        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->never())->method('getMethod');
        $request->expects($this->never())->method('getUri');

        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->once())->method('hasBodySchema')->willReturn(true);
        $responseDefinition->expects($this->once())->method('getBodySchema')->willReturn($bodySchema);

        $pagination = $this->createMock(Pagination::class);

        $paginationProvider = $this->createMock(PaginationInterface::class);
        $paginationProvider
            ->expects($this->once())
            ->method('support')
            ->with($response)
            ->willReturn(true)
        ;
        $paginationProvider
            ->expects($this->once())
            ->method('getPagination')
            ->with($data, $response)
            ->willReturn($pagination)
        ;

        $this->dataTransformer
            ->expects($this->once())
            ->method('transform')
            ->with('application/hal+json', $data)
            ->willReturn([
                [
                    'name' => 'foo',
                    'enabled' => true,
                    'expression' => '',
                    'createdBy' => 'john.doe@example.org',
                    'updatedBy' => 'jane.doe@example.org',
                    'dateCreated' => '2023-11-24T15:39:14+01:00',
                    'dateModified' => '2023-11-24T15:39:14+01:00',
                ],
                [
                    'name' => 'bar',
                    'enabled' => true,
                    'expression' => '',
                    'createdBy' => 'john.doe@example.org',
                    'updatedBy' => 'jane.doe@example.org',
                    'dateCreated' => '2023-11-24T15:39:14+01:00',
                    'dateModified' => '2023-11-24T15:39:14+01:00',
                ],
                [
                    'name' => 'baz',
                    'enabled' => false,
                    'expression' => '',
                    'createdBy' => 'john.doe@example.org',
                    'updatedBy' => 'jane.doe@example.org',
                    'dateCreated' => '2023-11-24T15:39:14+01:00',
                    'dateModified' => '2023-11-24T15:39:14+01:00',
                ],
            ])
        ;

        $denormalizer = $this->getDenormalizer();
        $resource = $denormalizer->denormalize(
            data: $data,
            type: ResourceInterface::class,
            context: [
                'response' => $response,
                'responseDefinition' => $responseDefinition,
                'request' => $request,
                'pagination' => $paginationProvider,
            ],
        );

        $this->assertInstanceOf(Collection::class, $resource);
        $this->assertSame(
            [
                [
                    'name' => 'foo',
                    'enabled' => true,
                    'expression' => '',
                    'createdBy' => 'john.doe@example.org',
                    'updatedBy' => 'jane.doe@example.org',
                    'dateCreated' => '2023-11-24T15:39:14+01:00',
                    'dateModified' => '2023-11-24T15:39:14+01:00',
                ],
                [
                    'name' => 'bar',
                    'enabled' => true,
                    'expression' => '',
                    'createdBy' => 'john.doe@example.org',
                    'updatedBy' => 'jane.doe@example.org',
                    'dateCreated' => '2023-11-24T15:39:14+01:00',
                    'dateModified' => '2023-11-24T15:39:14+01:00',
                ],
                [
                    'name' => 'baz',
                    'enabled' => false,
                    'expression' => '',
                    'createdBy' => 'john.doe@example.org',
                    'updatedBy' => 'jane.doe@example.org',
                    'dateCreated' => '2023-11-24T15:39:14+01:00',
                    'dateModified' => '2023-11-24T15:39:14+01:00',
                ],
            ],
            $resource->getData()
        );
        $this->assertSame(
            ['headers' => ['Content-Type' => ['application/json', 'application/hal+json']]],
            $resource->getMeta()
        );
        $this->assertTrue($resource->hasPagination());
        $this->assertSame($pagination, $resource->getPagination());
        $this->assertSame(
            [
                [
                    'name' => 'foo',
                    'enabled' => true,
                    'expression' => '',
                    'createdBy' => 'john.doe@example.org',
                    'updatedBy' => 'jane.doe@example.org',
                    'dateCreated' => '2023-11-24T15:39:14+01:00',
                    'dateModified' => '2023-11-24T15:39:14+01:00',
                ],
                [
                    'name' => 'bar',
                    'enabled' => true,
                    'expression' => '',
                    'createdBy' => 'john.doe@example.org',
                    'updatedBy' => 'jane.doe@example.org',
                    'dateCreated' => '2023-11-24T15:39:14+01:00',
                    'dateModified' => '2023-11-24T15:39:14+01:00',
                ],
                [
                    'name' => 'baz',
                    'enabled' => false,
                    'expression' => '',
                    'createdBy' => 'john.doe@example.org',
                    'updatedBy' => 'jane.doe@example.org',
                    'dateCreated' => '2023-11-24T15:39:14+01:00',
                    'dateModified' => '2023-11-24T15:39:14+01:00',
                ],
            ],
            $resource->getIterator()->getArrayCopy()
        );
    }

    public function testShouldValidateSupportedTypes(): void
    {
        $denormalizer = $this->getDenormalizer();
        $this->assertSame(
            [
                '*' => false,
                ResourceInterface::class => true,
            ],
            $denormalizer->getSupportedTypes(null)
        );
    }

    private function getDenormalizer(): ResourceDenormalizer
    {
        return new ResourceDenormalizer($this->dataTransformer);
    }
}
