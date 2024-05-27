<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle;

use Http\Client\HttpAsyncClient;
use Http\Promise\Promise;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use TwentytwoLabs\ApiValidator\Decoder\DecoderUtils;
use TwentytwoLabs\ApiValidator\Definition\OperationDefinition;
use TwentytwoLabs\ApiValidator\Definition\ResponseDefinition;
use TwentytwoLabs\ApiValidator\Schema;
use TwentytwoLabs\ApiValidator\Validator\MessageValidator;
use TwentytwoLabs\ApiServiceBundle\Exception\RequestViolations;
use TwentytwoLabs\ApiServiceBundle\Exception\ResponseViolations;
use TwentytwoLabs\ApiServiceBundle\Factory\RequestFactory;
use TwentytwoLabs\ApiServiceBundle\Model\ErrorInterface;
use TwentytwoLabs\ApiServiceBundle\Model\ResourceInterface;
use TwentytwoLabs\ApiServiceBundle\Pagination\PaginationInterface;

class ApiService
{
    private RequestFactory $requestFactory;
    private MessageValidator $messageValidator;
    private SerializerInterface $serializer;
    private ClientInterface|HttpAsyncClient $client;
    private Schema $schema;
    private ?LoggerInterface $logger;
    private ?PaginationInterface $pagination;
    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        RequestFactory $requestFactory,
        MessageValidator $messageValidator,
        SerializerInterface $serializer,
        ClientInterface|HttpAsyncClient $client,
        Schema $schema,
        LoggerInterface $logger = null,
        PaginationInterface $pagination = null,
        array $config = []
    ) {
        $this->requestFactory = $requestFactory;
        $this->messageValidator = $messageValidator;
        $this->serializer = $serializer;
        $this->client = $client;
        $this->schema = $schema;
        $this->logger = $logger;
        $this->pagination = $pagination;
        $this->config = $config;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @throws RequestViolations
     * @throws ResponseViolations
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function call(string $operationId = '', string $method = '', string $path = '', array $params = []): mixed
    {
        $operationDefinition = $this->schema->getOperationDefinition(
            operationId: $operationId,
            method: $method,
            path: $path
        );

        $request = $this->requestFactory->createRequestFromDefinition(
            $operationDefinition,
            $this->config['baseUri'],
            $params
        );

        $this->validateRequest($request, $operationDefinition);

        $this->logger?->info('Sending request:', ['request' => $request]);
        $response = $this->client->sendRequest($request);
        $this->logger?->info('Received response:', ['response' => $response]);

        $this->validateResponse($response, $operationDefinition);

        return $this->getDataFromResponse(
            $response,
            $operationDefinition->getResponseDefinition($response->getStatusCode()),
            $request
        );
    }

    /**
     * @param array<string, mixed> $params
     *
     * @throws \Exception
     */
    public function callAsync(
        string $operationId = '',
        string $method = '',
        string $path = '',
        array $params = []
    ): Promise {
        if (!$this->client instanceof HttpAsyncClient) {
            throw new \RuntimeException(sprintf('"%s" does not support async request', get_class($this->client)));
        }

        $operationDefinition = $this->schema->getOperationDefinition(
            operationId: $operationId,
            method: $method,
            path: $path
        );

        $request = $this->requestFactory->createRequestFromDefinition(
            $operationDefinition,
            $this->config['baseUri'],
            $params
        );
        $this->logger?->info('Sending request:', ['request' => $request]);
        $promise = $this->client->sendAsyncRequest($request);

        return $promise->then(
            function (ResponseInterface $response) use ($request, $operationDefinition) {
                $this->logger?->info('Received response:', ['response' => $response]);

                return $this->getDataFromResponse(
                    $response,
                    $operationDefinition->getResponseDefinition($response->getStatusCode()),
                    $request
                );
            }
        );
    }

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    public function withReturnResponse(bool $returnResponse): self
    {
        $new = clone $this;
        $new->config['returnResponse'] = $returnResponse;

        return $new;
    }

    private function getDataFromResponse(
        ResponseInterface $response,
        ResponseDefinition $definition,
        RequestInterface $request
    ): mixed {
        if (true === $this->config['returnResponse']) {
            return $response;
        }

        if (!$definition->hasBodySchema() || empty($response->getHeaderLine('Content-Type'))) {
            return null;
        }

        $statusCode = $response->getStatusCode();

        return $this->serializer->deserialize(
            $response->getBody(),
            $statusCode >= 400 ? ErrorInterface::class : ResourceInterface::class,
            DecoderUtils::extractFormatFromContentType($response->getHeaderLine('Content-Type')),
            [
                'response' => $response,
                'responseDefinition' => $definition,
                'request' => $request,
                'pagination' => $this->pagination,
            ]
        );
    }

    private function validateRequest(RequestInterface $request, OperationDefinition $definition): void
    {
        if (false === $this->config['validateRequest']) {
            return;
        }

        $this->messageValidator->validateRequest($request, $definition);
        if ($this->messageValidator->hasViolations()) {
            throw new RequestViolations($this->messageValidator->getViolations());
        }
    }

    private function validateResponse(ResponseInterface $response, OperationDefinition $definition): void
    {
        if (false === $this->config['validateResponse']) {
            return;
        }

        $this->messageValidator->validateResponse($response, $definition);
        if ($this->messageValidator->hasViolations()) {
            throw new ResponseViolations($this->messageValidator->getViolations());
        }
    }
}
