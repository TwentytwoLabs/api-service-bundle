<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Tests;

use Http\Client\HttpAsyncClient;
use Http\Promise\FulfilledPromise;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use TwentytwoLabs\ApiValidator\Definition\OperationDefinition;
use TwentytwoLabs\ApiValidator\Definition\ResponseDefinition;
use TwentytwoLabs\ApiValidator\Schema;
use TwentytwoLabs\ApiValidator\Validator\ConstraintViolation;
use TwentytwoLabs\ApiValidator\Validator\MessageValidator;
use TwentytwoLabs\ApiServiceBundle\ApiService;
use TwentytwoLabs\ApiServiceBundle\Exception\RequestViolations;
use TwentytwoLabs\ApiServiceBundle\Exception\ResponseViolations;
use TwentytwoLabs\ApiServiceBundle\Factory\RequestFactory;
use TwentytwoLabs\ApiServiceBundle\Model\ErrorInterface;
use TwentytwoLabs\ApiServiceBundle\Model\ResourceInterface;
use TwentytwoLabs\ApiServiceBundle\Pagination\PaginationInterface;

final class ApiServiceTest extends TestCase
{
    private RequestFactory|MockObject $requestFactory;
    private MessageValidator|MockObject $messageValidator;
    private SerializerInterface|MockObject $serializer;
    private ClientInterface|HttpAsyncClient|MockObject $client;
    private Schema|MockObject $schema;
    private LoggerInterface|MockObject $logger;
    private PaginationInterface|MockObject $pagination;

    private HttpAsyncClient|MockObject $clientAsync;

    protected function setUp(): void
    {
        $this->requestFactory = $this->createMock(RequestFactory::class);
        $this->messageValidator = $this->createMock(MessageValidator::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->client = $this->createMock(ClientInterface::class);
        $this->schema = $this->createMock(Schema::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->pagination = $this->createMock(PaginationInterface::class);

        $this->clientAsync = $this->createMock(HttpAsyncClient::class);
    }

    public function testShouldSendRequestDirectlyWithoutRequestAndResponseValidation(): void
    {
        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->once())->method('hasBodySchema')->willReturn(true);

        $operationDefinition = $this->createMock(OperationDefinition::class);
        $operationDefinition->expects($this->once())->method('getResponseDefinition')->with(200)->willReturn($responseDefinition);

        $this->schema->expects($this->once())->method('getOperationDefinition')->with()->willReturn($operationDefinition);

        $request = $this->createMock(RequestInterface::class);

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('__toString')->willReturn('{"foo":"bar"}');

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->exactly(2))->method('getStatusCode')->willReturn(200);
        $response->expects($this->exactly(2))->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $response->expects($this->once())->method('getBody')->willReturn($stream);

        $this->requestFactory
            ->expects($this->once())
            ->method('createRequestFromDefinition')
            ->with($operationDefinition, 'http://example.org', ['foo' => 'bar'])
            ->willReturn($request)
        ;

        $this->messageValidator->expects($this->never())->method('validateRequest');
        $this->messageValidator->expects($this->never())->method('hasViolations');
        $this->messageValidator->expects($this->never())->method('getViolations');
        $matcher = $this->exactly(2);
        $this->logger
            ->expects($matcher)
            ->method('info')
            ->willReturnCallback(function (string $message, array $context) use ($matcher, $request, $response) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('Sending request:', $message);
                    $this->assertSame(['request' => $request], $context);
                }

                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('Received response:', $message);
                    $this->assertSame(['response' => $response], $context);
                }
            })
        ;
        $this->client->expects($this->once())->method('sendRequest')->with($request)->willReturn($response);
        $this->messageValidator->expects($this->never())->method('validateResponse');

        $item = $this->createMock(ResourceInterface::class);

        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with(
                '{"foo":"bar"}',
                ResourceInterface::class,
                'json',
                [
                    'response' => $response,
                    'responseDefinition' => $responseDefinition,
                    'request' => $request,
                    'pagination' => $this->pagination,
                ]
            )
            ->willReturn($item)
        ;

        $apiService = $this->getApiService(validateRequest: false, validateResponse: false);
        $this->assertSame($this->schema, $apiService->getSchema());
        $this->assertSame($item, $apiService->call(operationId: 'getFooCollection', params: ['foo' => 'bar']));
    }

    public function testShouldNotSendRequestDirectlyWithRequestAndResponseValidationBecauseRequestIsNotValid(): void
    {
        $this->expectException(RequestViolations::class);
        $this->expectExceptionMessage('foo');

        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->never())->method('hasBodySchema');

        $operationDefinition = $this->createMock(OperationDefinition::class);
        $operationDefinition->expects($this->never())->method('getResponseDefinition');

        $this->schema->expects($this->once())->method('getOperationDefinition')->with()->willReturn($operationDefinition);

        $request = $this->createMock(RequestInterface::class);

        $this->requestFactory
            ->expects($this->once())
            ->method('createRequestFromDefinition')
            ->with($operationDefinition, 'http://example.org', ['foo' => 'bar'])
            ->willReturn($request)
        ;

        $violation = $this->createMock(ConstraintViolation::class);
        $violation->expects($this->once())->method('getProperty')->willReturn('foo');
        $violation->expects($this->once())->method('getMessage')->willReturn('bar');
        $violation->expects($this->once())->method('getConstraint')->willReturn('baz');
        $violation->expects($this->once())->method('getLocation')->willReturn('header');

        $this->messageValidator
            ->expects($this->once())
            ->method('validateRequest')
            ->with($request, $operationDefinition)
        ;
        $this->messageValidator->expects($this->once())->method('hasViolations')->willReturn(true);
        $this->messageValidator->expects($this->once())->method('getViolations')->willReturn([$violation]);
        $this->logger->expects($this->never())->method('info');
        $this->client->expects($this->never())->method('sendRequest');
        $this->messageValidator->expects($this->never())->method('validateResponse');

        $this->serializer->expects($this->never())->method('deserialize');

        $apiService = $this->getApiService();
        $this->assertSame($this->schema, $apiService->getSchema());
        $apiService->call(operationId: 'getFooCollection', params: ['foo' => 'bar']);
    }

    public function testShouldSendRequestDirectlyWithRequestAndResponseValidationButResponseIsNotValid(): void
    {
        $this->expectException(ResponseViolations::class);
        $this->expectExceptionMessage('foo');

        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->never())->method('hasBodySchema');

        $operationDefinition = $this->createMock(OperationDefinition::class);
        $operationDefinition->expects($this->never())->method('getResponseDefinition');

        $this->schema->expects($this->once())->method('getOperationDefinition')->with()->willReturn($operationDefinition);

        $request = $this->createMock(RequestInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->never())->method('getStatusCode');
        $response->expects($this->never())->method('getHeaderLine');
        $response->expects($this->never())->method('getBody');

        $this->requestFactory
            ->expects($this->once())
            ->method('createRequestFromDefinition')
            ->with($operationDefinition, 'http://example.org', ['foo' => 'bar'])
            ->willReturn($request)
        ;

        $violation = $this->createMock(ConstraintViolation::class);
        $violation->expects($this->once())->method('getProperty')->willReturn('foo');
        $violation->expects($this->once())->method('getMessage')->willReturn('bar');
        $violation->expects($this->once())->method('getConstraint')->willReturn('baz');
        $violation->expects($this->once())->method('getLocation')->willReturn('header');

        $this->messageValidator
            ->expects($this->once())
            ->method('validateRequest')
            ->with($request, $operationDefinition)
        ;
        $this->messageValidator->expects($this->exactly(2))->method('hasViolations')->willReturnOnConsecutiveCalls(false, true);
        $this->messageValidator->expects($this->once())->method('getViolations')->willReturn([$violation]);
        $matcher = $this->exactly(2);
        $this->logger
            ->expects($matcher)
            ->method('info')
            ->willReturnCallback(function (string $message, array $context) use ($matcher, $request, $response) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('Sending request:', $message);
                    $this->assertSame(['request' => $request], $context);
                }

                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('Received response:', $message);
                    $this->assertSame(['response' => $response], $context);
                }
            })
        ;
        $this->client->expects($this->once())->method('sendRequest')->with($request)->willReturn($response);
        $this->messageValidator->expects($this->once())->method('validateResponse')->with($response, $operationDefinition);

        $this->serializer->expects($this->never())->method('deserialize');

        $apiService = $this->getApiService();
        $this->assertSame($this->schema, $apiService->getSchema());
        $apiService->call(operationId: 'getFooCollection', params: ['foo' => 'bar']);
    }

    public function testShouldSendRequestDirectlyWithRequestAndResponseValidation(): void
    {
        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->once())->method('hasBodySchema')->willReturn(true);

        $operationDefinition = $this->createMock(OperationDefinition::class);
        $operationDefinition->expects($this->once())->method('getResponseDefinition')->with(200)->willReturn($responseDefinition);

        $this->schema->expects($this->once())->method('getOperationDefinition')->with()->willReturn($operationDefinition);

        $request = $this->createMock(RequestInterface::class);

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('__toString')->willReturn('{"foo":"bar"}');

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->exactly(2))->method('getStatusCode')->willReturn(200);
        $response->expects($this->exactly(2))->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $response->expects($this->once())->method('getBody')->willReturn($stream);

        $this->requestFactory
            ->expects($this->once())
            ->method('createRequestFromDefinition')
            ->with($operationDefinition, 'http://example.org', ['foo' => 'bar'])
            ->willReturn($request)
        ;

        $this->messageValidator
            ->expects($this->once())
            ->method('validateRequest')
            ->with($request, $operationDefinition)
        ;
        $this->messageValidator->expects($this->exactly(2))->method('hasViolations')->willReturn(false);
        $this->messageValidator->expects($this->never())->method('getViolations');
        $matcher = $this->exactly(2);
        $this->logger
            ->expects($matcher)
            ->method('info')
            ->willReturnCallback(function (string $message, array $context) use ($matcher, $request, $response) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('Sending request:', $message);
                    $this->assertSame(['request' => $request], $context);
                }

                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('Received response:', $message);
                    $this->assertSame(['response' => $response], $context);
                }
            })
        ;
        $this->client->expects($this->once())->method('sendRequest')->with($request)->willReturn($response);
        $this->messageValidator->expects($this->once())->method('validateResponse')->with($response, $operationDefinition);

        $item = $this->createMock(ResourceInterface::class);

        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with(
                '{"foo":"bar"}',
                ResourceInterface::class,
                'json',
                [
                    'response' => $response,
                    'responseDefinition' => $responseDefinition,
                    'request' => $request,
                    'pagination' => $this->pagination,
                ]
            )
            ->willReturn($item)
        ;

        $apiService = $this->getApiService();
        $this->assertSame($this->schema, $apiService->getSchema());
        $this->assertSame($item, $apiService->call(operationId: 'getFooCollection', params: ['foo' => 'bar']));
    }

    public function testShouldSendRequestDirectlyWithRequestAndResponseValidationAndReturnResponse(): void
    {
        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->never())->method('hasBodySchema');

        $operationDefinition = $this->createMock(OperationDefinition::class);
        $operationDefinition->expects($this->once())->method('getResponseDefinition')->with(200)->willReturn($responseDefinition);

        $this->schema->expects($this->once())->method('getOperationDefinition')->with()->willReturn($operationDefinition);

        $request = $this->createMock(RequestInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getStatusCode')->willReturn(200);
        $response->expects($this->never())->method('getHeaderLine');

        $this->requestFactory
            ->expects($this->once())
            ->method('createRequestFromDefinition')
            ->with($operationDefinition, 'http://example.org', ['foo' => 'bar'])
            ->willReturn($request)
        ;

        $this->messageValidator
            ->expects($this->once())
            ->method('validateRequest')
            ->with($request, $operationDefinition)
        ;
        $this->messageValidator->expects($this->exactly(2))->method('hasViolations')->willReturn(false);
        $this->messageValidator->expects($this->never())->method('getViolations');
        $matcher = $this->exactly(2);
        $this->logger
            ->expects($matcher)
            ->method('info')
            ->willReturnCallback(function (string $message, array $context) use ($matcher, $request, $response) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('Sending request:', $message);
                    $this->assertSame(['request' => $request], $context);
                }

                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('Received response:', $message);
                    $this->assertSame(['response' => $response], $context);
                }
            })
        ;
        $this->client->expects($this->once())->method('sendRequest')->with($request)->willReturn($response);
        $this->messageValidator->expects($this->once())->method('validateResponse')->with($response, $operationDefinition);

        $this->serializer->expects($this->never())->method('deserialize');

        $apiService = $this->getApiService();
        $newApiService = $apiService->withReturnResponse(true);
        $this->assertNotSame($newApiService, $apiService);
        $this->assertSame($this->schema, $newApiService->getSchema());
        $this->assertSame($response, $newApiService->call(operationId: 'getFooCollection', params: ['foo' => 'bar']));
    }

    public function testShouldSendRequestDirectlyWithRequestAndResponseValidationAndWithOutLogger(): void
    {
        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->once())->method('hasBodySchema')->willReturn(true);

        $operationDefinition = $this->createMock(OperationDefinition::class);
        $operationDefinition->expects($this->once())->method('getResponseDefinition')->with(200)->willReturn($responseDefinition);

        $this->schema->expects($this->once())->method('getOperationDefinition')->with()->willReturn($operationDefinition);

        $request = $this->createMock(RequestInterface::class);

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('__toString')->willReturn('{"foo":"bar"}');

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->exactly(2))->method('getStatusCode')->willReturn(200);
        $response->expects($this->exactly(2))->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $response->expects($this->once())->method('getBody')->willReturn($stream);

        $this->requestFactory
            ->expects($this->once())
            ->method('createRequestFromDefinition')
            ->with($operationDefinition, 'http://example.org', ['foo' => 'bar'])
            ->willReturn($request)
        ;

        $this->messageValidator
            ->expects($this->once())
            ->method('validateRequest')
            ->with($request, $operationDefinition)
        ;
        $this->messageValidator->expects($this->exactly(2))->method('hasViolations')->willReturn(false);
        $this->messageValidator->expects($this->never())->method('getViolations');
        $this->logger->expects($this->never())->method('info');
        $this->client->expects($this->once())->method('sendRequest')->with($request)->willReturn($response);
        $this->messageValidator->expects($this->once())->method('validateResponse')->with($response, $operationDefinition);

        $item = $this->createMock(ResourceInterface::class);

        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with(
                '{"foo":"bar"}',
                ResourceInterface::class,
                'json',
                [
                    'response' => $response,
                    'responseDefinition' => $responseDefinition,
                    'request' => $request,
                    'pagination' => $this->pagination,
                ]
            )
            ->willReturn($item)
        ;

        $apiService = $this->getApiService(withLogger: false);
        $this->assertSame($this->schema, $apiService->getSchema());
        $this->assertSame($item, $apiService->call(operationId: 'getFooCollection', params: ['foo' => 'bar']));
    }

    public function testShouldSendRequestDirectlyWithRequestAndResponseValidationAndReturnResponseAndWithOutLogger(): void
    {
        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->never())->method('hasBodySchema');

        $operationDefinition = $this->createMock(OperationDefinition::class);
        $operationDefinition->expects($this->once())->method('getResponseDefinition')->with(200)->willReturn($responseDefinition);

        $this->schema->expects($this->once())->method('getOperationDefinition')->with()->willReturn($operationDefinition);

        $request = $this->createMock(RequestInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getStatusCode')->willReturn(200);
        $response->expects($this->never())->method('getHeaderLine');

        $this->requestFactory
            ->expects($this->once())
            ->method('createRequestFromDefinition')
            ->with($operationDefinition, 'http://example.org', ['foo' => 'bar'])
            ->willReturn($request)
        ;

        $this->messageValidator
            ->expects($this->once())
            ->method('validateRequest')
            ->with($request, $operationDefinition)
        ;
        $this->messageValidator->expects($this->exactly(2))->method('hasViolations')->willReturn(false);
        $this->messageValidator->expects($this->never())->method('getViolations');
        $this->logger->expects($this->never())->method('info');
        $this->client->expects($this->once())->method('sendRequest')->with($request)->willReturn($response);
        $this->messageValidator->expects($this->once())->method('validateResponse')->with($response, $operationDefinition);

        $this->serializer->expects($this->never())->method('deserialize');

        $apiService = $this->getApiService(withLogger: false);
        $newApiService = $apiService->withReturnResponse(true);
        $this->assertNotSame($newApiService, $apiService);
        $this->assertSame($this->schema, $newApiService->getSchema());
        $this->assertSame($response, $newApiService->call(operationId: 'getFooCollection', params: ['foo' => 'bar']));
    }

    public function testShouldSendRequestDirectlyWithRequestAndResponseValidationWhenThereAreSomeErrors(): void
    {
        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->once())->method('hasBodySchema')->willReturn(true);

        $operationDefinition = $this->createMock(OperationDefinition::class);
        $operationDefinition->expects($this->once())->method('getResponseDefinition')->with(400)->willReturn($responseDefinition);

        $this->schema->expects($this->once())->method('getOperationDefinition')->with()->willReturn($operationDefinition);

        $request = $this->createMock(RequestInterface::class);

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('__toString')->willReturn('{"foo":"bar"}');

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->exactly(2))->method('getStatusCode')->willReturn(400);
        $response->expects($this->exactly(2))->method('getHeaderLine')->with('Content-Type')->willReturn('application/json');
        $response->expects($this->once())->method('getBody')->willReturn($stream);

        $this->requestFactory
            ->expects($this->once())
            ->method('createRequestFromDefinition')
            ->with($operationDefinition, 'http://example.org', ['foo' => 'bar'])
            ->willReturn($request)
        ;

        $this->messageValidator
            ->expects($this->once())
            ->method('validateRequest')
            ->with($request, $operationDefinition)
        ;
        $this->messageValidator->expects($this->exactly(2))->method('hasViolations')->willReturn(false);
        $this->messageValidator->expects($this->never())->method('getViolations');
        $matcher = $this->exactly(2);
        $this->logger
            ->expects($matcher)
            ->method('info')
            ->willReturnCallback(function (string $message, array $context) use ($matcher, $request, $response) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('Sending request:', $message);
                    $this->assertSame(['request' => $request], $context);
                }

                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('Received response:', $message);
                    $this->assertSame(['response' => $response], $context);
                }
            })
        ;
        $this->client->expects($this->once())->method('sendRequest')->with($request)->willReturn($response);
        $this->messageValidator->expects($this->once())->method('validateResponse')->with($response, $operationDefinition);

        $item = $this->createMock(ErrorInterface::class);

        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with(
                '{"foo":"bar"}',
                ErrorInterface::class,
                'json',
                [
                    'response' => $response,
                    'responseDefinition' => $responseDefinition,
                    'request' => $request,
                    'pagination' => $this->pagination,
                ]
            )
            ->willReturn($item)
        ;

        $apiService = $this->getApiService();
        $this->assertSame($this->schema, $apiService->getSchema());
        $this->assertSame($item, $apiService->call(operationId: 'getFooCollection', params: ['foo' => 'bar']));
    }

    public function testShouldSendRequestDirectlyWithRequestAndResponseValidationWhenThereAreNoBodySchema(): void
    {
        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->once())->method('hasBodySchema')->willReturn(false);

        $operationDefinition = $this->createMock(OperationDefinition::class);
        $operationDefinition->expects($this->once())->method('getResponseDefinition')->with(204)->willReturn($responseDefinition);

        $this->schema->expects($this->once())->method('getOperationDefinition')->with()->willReturn($operationDefinition);

        $request = $this->createMock(RequestInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getStatusCode')->willReturn(204);
        $response->expects($this->never())->method('getHeaderLine');
        $response->expects($this->never())->method('getBody');

        $this->requestFactory
            ->expects($this->once())
            ->method('createRequestFromDefinition')
            ->with($operationDefinition, 'http://example.org', ['foo' => 'bar'])
            ->willReturn($request)
        ;

        $this->messageValidator
            ->expects($this->once())
            ->method('validateRequest')
            ->with($request, $operationDefinition)
        ;
        $this->messageValidator->expects($this->exactly(2))->method('hasViolations')->willReturn(false);
        $this->messageValidator->expects($this->never())->method('getViolations');
        $matcher = $this->exactly(2);
        $this->logger
            ->expects($matcher)
            ->method('info')
            ->willReturnCallback(function (string $message, array $context) use ($matcher, $request, $response) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('Sending request:', $message);
                    $this->assertSame(['request' => $request], $context);
                }

                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('Received response:', $message);
                    $this->assertSame(['response' => $response], $context);
                }
            })
        ;
        $this->client->expects($this->once())->method('sendRequest')->with($request)->willReturn($response);
        $this->messageValidator->expects($this->once())->method('validateResponse')->with($response, $operationDefinition);

        $item = $this->createMock(ErrorInterface::class);

        $this->serializer->expects($this->never())->method('deserialize');

        $apiService = $this->getApiService();
        $this->assertSame($this->schema, $apiService->getSchema());
        $this->assertNull($apiService->call(operationId: 'getFooCollection', params: ['foo' => 'bar']));
    }

    public function testShouldSendRequestDirectlyWithRequestAndResponseValidationWhenThereAreNoContentType(): void
    {
        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->once())->method('hasBodySchema')->willReturn(true);

        $operationDefinition = $this->createMock(OperationDefinition::class);
        $operationDefinition->expects($this->once())->method('getResponseDefinition')->with(204)->willReturn($responseDefinition);

        $this->schema->expects($this->once())->method('getOperationDefinition')->with()->willReturn($operationDefinition);

        $request = $this->createMock(RequestInterface::class);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())->method('getStatusCode')->willReturn(204);
        $response->expects($this->once())->method('getHeaderLine')->with('Content-Type')->willReturn('');
        $response->expects($this->never())->method('getBody');

        $this->requestFactory
            ->expects($this->once())
            ->method('createRequestFromDefinition')
            ->with($operationDefinition, 'http://example.org', ['foo' => 'bar'])
            ->willReturn($request)
        ;

        $this->messageValidator
            ->expects($this->once())
            ->method('validateRequest')
            ->with($request, $operationDefinition)
        ;
        $this->messageValidator->expects($this->exactly(2))->method('hasViolations')->willReturn(false);
        $this->messageValidator->expects($this->never())->method('getViolations');
        $matcher = $this->exactly(2);
        $this->logger
            ->expects($matcher)
            ->method('info')
            ->willReturnCallback(function (string $message, array $context) use ($matcher, $request, $response) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('Sending request:', $message);
                    $this->assertSame(['request' => $request], $context);
                }

                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('Received response:', $message);
                    $this->assertSame(['response' => $response], $context);
                }
            })
        ;
        $this->client->expects($this->once())->method('sendRequest')->with($request)->willReturn($response);
        $this->messageValidator->expects($this->once())->method('validateResponse')->with($response, $operationDefinition);

        $item = $this->createMock(ErrorInterface::class);

        $this->serializer->expects($this->never())->method('deserialize');

        $apiService = $this->getApiService();
        $this->assertSame($this->schema, $apiService->getSchema());
        $this->assertNull($apiService->call(operationId: 'getFooCollection', params: ['foo' => 'bar']));
    }

    public function testShouldNotSendAsyncRequestBecauseItIsNotAGoodClient(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('#does not support async request$#');

        $this->schema->expects($this->never())->method('getOperationDefinition');

        $this->requestFactory->expects($this->never())->method('createRequestFromDefinition');

        $this->logger->expects($this->never())->method('info');

        $this->serializer->expects($this->never())->method('deserialize');

        $this->clientAsync->expects($this->never())->method('sendAsyncRequest');

        $apiService = $this->getApiServiceForAsync(withGoodClient: false);
        $this->assertSame($this->schema, $apiService->getSchema());
        $apiService->callAsync(operationId: 'getFooCollection', params: ['foo' => 'bar']);
    }

    public function testShouldSendAsyncRequest(): void
    {
        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->once())->method('hasBodySchema')->willReturn(true);

        $operationDefinition = $this->createMock(OperationDefinition::class);
        $operationDefinition->expects($this->once())->method('getResponseDefinition')->willReturn($responseDefinition);

        $this->schema->expects($this->once())->method('getOperationDefinition')->willReturn($operationDefinition);

        $request = $this->createMock(RequestInterface::class);

        $this->requestFactory
            ->expects($this->once())
            ->method('createRequestFromDefinition')
            ->with($operationDefinition, 'http://example.org', ['foo' => 'bar'])
            ->willReturn($request)
        ;

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('__toString')->willReturn('{"foo":"bar"}');

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->exactly(2))->method('getStatusCode')->willReturn(200);
        $response
            ->expects($this->exactly(2))
            ->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json')
        ;
        $response->expects($this->once())->method('getBody')->willReturn($stream);

        $matcher = $this->exactly(2);
        $this->logger
            ->expects($matcher)
            ->method('info')
            ->willReturnCallback(function (string $message, array $context) use ($matcher, $request, $response) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('Sending request:', $message);
                    $this->assertSame(['request' => $request], $context);
                }

                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('Received response:', $message);
                    $this->assertSame(['response' => $response], $context);
                }
            })
        ;

        $item = $this->createMock(ResourceInterface::class);

        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with(
                '{"foo":"bar"}',
                ResourceInterface::class,
                'json',
                [
                    'response' => $response,
                    'responseDefinition' => $responseDefinition,
                    'request' => $request,
                    'pagination' => $this->pagination,
                ]
            )
            ->willReturn($item)
        ;

        $promise = new FulfilledPromise($response);

        $this->clientAsync->expects($this->once())->method('sendAsyncRequest')->with($request)->willReturn($promise);

        $apiService = $this->getApiServiceForAsync();
        $this->assertSame($this->schema, $apiService->getSchema());
        $promise = $apiService->callAsync(operationId: 'getFooCollection', params: ['foo' => 'bar']);
        $this->assertSame($item, $promise->wait());
    }

    public function testShouldSendAsyncRequestWithoutLogger(): void
    {
        $responseDefinition = $this->createMock(ResponseDefinition::class);
        $responseDefinition->expects($this->once())->method('hasBodySchema')->willReturn(true);

        $operationDefinition = $this->createMock(OperationDefinition::class);
        $operationDefinition->expects($this->once())->method('getResponseDefinition')->willReturn($responseDefinition);

        $this->schema->expects($this->once())->method('getOperationDefinition')->willReturn($operationDefinition);

        $request = $this->createMock(RequestInterface::class);

        $this->requestFactory
            ->expects($this->once())
            ->method('createRequestFromDefinition')
            ->with($operationDefinition, 'http://example.org', ['foo' => 'bar'])
            ->willReturn($request)
        ;

        $stream = $this->createMock(StreamInterface::class);
        $stream->expects($this->once())->method('__toString')->willReturn('{"foo":"bar"}');

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->exactly(2))->method('getStatusCode')->willReturn(200);
        $response
            ->expects($this->exactly(2))
            ->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json')
        ;
        $response->expects($this->once())->method('getBody')->willReturn($stream);

        $this->logger->expects($this->never())->method('info');

        $item = $this->createMock(ResourceInterface::class);

        $this->serializer
            ->expects($this->once())
            ->method('deserialize')
            ->with(
                '{"foo":"bar"}',
                ResourceInterface::class,
                'json',
                [
                    'response' => $response,
                    'responseDefinition' => $responseDefinition,
                    'request' => $request,
                    'pagination' => $this->pagination,
                ]
            )
            ->willReturn($item)
        ;

        $promise = new FulfilledPromise($response);

        $this->clientAsync->expects($this->once())->method('sendAsyncRequest')->with($request)->willReturn($promise);

        $apiService = $this->getApiServiceForAsync(withLogger: false);
        $this->assertSame($this->schema, $apiService->getSchema());
        $promise = $apiService->callAsync(operationId: 'getFooCollection', params: ['foo' => 'bar']);
        $this->assertSame($item, $promise->wait());
    }

    private function getApiService(
        bool $validateRequest = true,
        bool $validateResponse = true,
        bool $withLogger = true
    ): ApiService {
        return new ApiService(
            $this->requestFactory,
            $this->messageValidator,
            $this->serializer,
            $this->client,
            $this->schema,
            $withLogger ? $this->logger : null,
            $this->pagination,
            [
                'baseUri' => 'http://example.org',
                'validateRequest' => $validateRequest,
                'validateResponse' => $validateResponse,
                'returnResponse' => false,
            ]
        );
    }

    private function getApiServiceForAsync(bool $withGoodClient = true, bool $withLogger = true): ApiService
    {
        return new ApiService(
            $this->requestFactory,
            $this->messageValidator,
            $this->serializer,
            $withGoodClient ? $this->clientAsync : $this->client,
            $this->schema,
            $withLogger ? $this->logger : null,
            $this->pagination,
            [
                'baseUri' => 'http://example.org',
                'validateRequest' => true,
                'validateResponse' => true,
                'returnResponse' => false,
            ]
        );
    }
}
