<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Tests\Factory;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Rize\UriTemplate;
use Symfony\Component\Serializer\SerializerInterface;
use TwentytwoLabs\ApiValidator\Definition\OperationDefinition;
use TwentytwoLabs\ApiValidator\Definition\Parameter;
use TwentytwoLabs\ApiValidator\Definition\Parameters;
use TwentytwoLabs\ApiServiceBundle\Factory\RequestFactory;

final class RequestFactoryTest extends TestCase
{
    private RequestFactoryInterface|MockObject $requestFactory;
    private UriTemplate|MockObject $uriTemplate;
    private UriFactoryInterface|MockObject $uriFactory;
    private StreamFactoryInterface|MockObject $streamFactory;
    private SerializerInterface|MockObject $serializer;

    protected function setUp(): void
    {
        $this->requestFactory = $this->createMock(RequestFactoryInterface::class);
        $this->uriTemplate = $this->createMock(UriTemplate::class);
        $this->uriFactory = $this->createMock(UriFactoryInterface::class);
        $this->streamFactory = $this->createMock(StreamFactoryInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
    }

    public function testShouldCreateRequestFromDefinition(): void
    {
        $requestParameters = [
            'x-uid' => new Parameter(
                location: 'header',
                name: 'x-uid',
                required: false,
                schema: ['type' => 'string']
            ),
            'body' => new Parameter(
                location: 'body',
                name: 'body',
                required: true,
                schema: [
                    'type' => 'object',
                    'properties' => [
                        'foo' => ['type' => 'string', 'default' => 'bar', 'nullable' => false],
                        'username' => ['type' => 'string', 'default' => null, 'nullable' => false],
                        'password' => ['type' => 'string', 'default' => null, 'nullable' => false],
                    ],
                    'required' => ['foo', 'username', 'password'],
                ],
            ),
            'content-type' => new Parameter(
                location: 'header',
                name: 'content-type',
                required: true,
                schema: [
                    'type' => 'string',
                    'default' => 'application/json',
                    'enum' => ['application/json'],
                ],
            ),
            'accept' => new Parameter(
                location: 'header',
                name: 'accept',
                required: true,
                schema: [
                    'type' => 'string',
                    'default' => 'application/json',
                    'enum' => ['application/json', 'application/problem+json'],
                ],
            ),
        ];

        $definition = $this->createMock(OperationDefinition::class);
        $definition
            ->expects($this->once())
            ->method('getRequestParameters')
            ->willReturn(new Parameters($requestParameters))
        ;
        $definition->expects($this->exactly(2))->method('getMethod')->willReturn('POST');
        $definition->expects($this->once())->method('getPathTemplate')->willReturn('/login_check');
        $params = [
            'x-uid' => '123456',
            'foo' => 'bar',
            'body' => [
                'username' => 'john.doe@example.org',
                'password' => 'azerty',
            ],
        ];

        $this->serializer
            ->expects($this->once())
            ->method('serialize')
            ->with(['username' => 'john.doe@example.org', 'password' => 'azerty', 'foo' => 'bar'], 'json')
            ->willReturn('{"username":"john.doe@example.org","password":"azerty","foo":"bar"}')
        ;

        $this->uriTemplate
            ->expects($this->once())
            ->method('expand')
            ->with('/login_check', [])
            ->willReturn('/login_check')
        ;

        $uri = $this->createMock(UriInterface::class);
        $uri->expects($this->once())->method('withPath')->with('/login_check')->willReturn($uri);
        $uri->expects($this->once())->method('withQuery')->with('')->willReturn($uri);

        $this->uriFactory->expects($this->once())->method('createUri')->with('http://example.org')->willReturn($uri);

        $stream = $this->createMock(StreamInterface::class);

        $this->streamFactory
            ->expects($this->once())
            ->method('createStream')
            ->with('{"username":"john.doe@example.org","password":"azerty","foo":"bar"}')
            ->willReturn($stream)
        ;

        $matcher = $this->exactly(3);

        $request = $this->createMock(RequestInterface::class);
        $request
            ->expects($matcher)
            ->method('withHeader')
            ->willReturnCallback(function (string $name, string $value) use ($matcher, $request) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('content-type', $name);
                    $this->assertSame('application/json', $value);
                }

                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('accept', $name);
                    $this->assertSame('application/json', $value);
                }

                if (3 === $matcher->numberOfInvocations()) {
                    $this->assertSame('x-uid', $name);
                    $this->assertSame('123456', $value);
                }

                return $request;
            })
        ;
        $request->expects($this->once())->method('withBody')->with($stream)->willReturnSelf();

        $this->requestFactory
            ->expects($this->once())
            ->method('createRequest')
            ->with('POST', $uri)
            ->willReturn($request)
        ;

        $factory = $this->getFactory();
        $this->assertSame($request, $factory->createRequestFromDefinition($definition, 'http://example.org', $params));
    }

    public function testShouldCreateRequestFromDefinitionWithFormData(): void
    {
        $requestParameters = [
            'x-uid' => new Parameter(
                location: 'header',
                name: 'x-uid',
                required: false,
                schema: ['type' => 'string']
            ),
            'body' => new Parameter(
                location: 'formData',
                name: 'body',
                required: true,
                schema: [
                    'type' => 'object',
                    'properties' => [
                        'foo' => ['type' => 'string', 'default' => 'bar', 'nullable' => false],
                        'username' => ['type' => 'string', 'default' => null, 'nullable' => false],
                        'password' => ['type' => 'string', 'default' => null, 'nullable' => false],
                    ],
                    'required' => ['foo', 'username', 'password'],
                ],
            ),
            'content-type' => new Parameter(
                location: 'header',
                name: 'content-type',
                required: true,
                schema: [
                    'type' => 'string',
                    'default' => 'application/json',
                    'enum' => ['application/json'],
                ],
            ),
            'accept' => new Parameter(
                location: 'header',
                name: 'accept',
                required: true,
                schema: [
                    'type' => 'string',
                    'default' => 'application/json',
                    'enum' => ['application/json', 'application/problem+json'],
                ],
            ),
        ];

        $definition = $this->createMock(OperationDefinition::class);
        $definition
            ->expects($this->once())
            ->method('getRequestParameters')
            ->willReturn(new Parameters($requestParameters))
        ;
        $definition->expects($this->exactly(2))->method('getMethod')->willReturn('POST');
        $definition->expects($this->once())->method('getPathTemplate')->willReturn('/login_check');
        $params = [
            'x-uid' => '123456',
            'foo' => 'bar',
            'body' => [
                'username' => 'john.doe@example.org',
                'password' => 'azerty',
            ],
        ];

        $this->serializer->expects($this->never())->method('serialize');

        $this->uriTemplate
            ->expects($this->once())
            ->method('expand')
            ->with('/login_check', [])
            ->willReturn('/login_check')
        ;

        $uri = $this->createMock(UriInterface::class);
        $uri->expects($this->once())->method('withPath')->with('/login_check')->willReturn($uri);
        $uri->expects($this->once())->method('withQuery')->with('')->willReturn($uri);

        $this->uriFactory->expects($this->once())->method('createUri')->with('http://example.org')->willReturn($uri);

        $stream = $this->createMock(StreamInterface::class);

        $this->streamFactory
            ->expects($this->once())
            ->method('createStream')
            ->with('john.doe@example.org&azerty')
            ->willReturn($stream)
        ;

        $matcher = $this->exactly(3);

        $request = $this->createMock(RequestInterface::class);
        $request
            ->expects($matcher)
            ->method('withHeader')
            ->willReturnCallback(function (string $name, string $value) use ($matcher, $request) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('content-type', $name);
                    $this->assertSame('application/json', $value);
                }

                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('accept', $name);
                    $this->assertSame('application/json', $value);
                }

                if (3 === $matcher->numberOfInvocations()) {
                    $this->assertSame('x-uid', $name);
                    $this->assertSame('123456', $value);
                }

                return $request;
            })
        ;
        $request->expects($this->once())->method('withBody')->with($stream)->willReturnSelf();

        $this->requestFactory
            ->expects($this->once())
            ->method('createRequest')
            ->with('POST', $uri)
            ->willReturn($request)
        ;

        $factory = $this->getFactory();
        $this->assertSame($request, $factory->createRequestFromDefinition($definition, 'http://example.org', $params));
    }

    private function getFactory(): RequestFactory
    {
        return new RequestFactory(
            $this->requestFactory,
            $this->uriTemplate,
            $this->uriFactory,
            $this->streamFactory,
            $this->serializer
        );
    }
}
