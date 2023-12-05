<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Factory;

use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use TwentytwoLabs\ApiValidator\Factory\SchemaFactoryInterface;
use TwentytwoLabs\ApiValidator\Validator\MessageValidator;
use TwentytwoLabs\ApiServiceBundle\ApiService;
use TwentytwoLabs\ApiServiceBundle\Pagination\PaginationInterface;

final class ApiServiceFactory
{
    private RequestFactory $requestFactory;
    private MessageValidator $messageValidator;
    private SerializerInterface $serializer;

    public function __construct(
        RequestFactory $requestFactory,
        MessageValidator $messageValidator,
        SerializerInterface $serializer
    ) {
        $this->requestFactory = $requestFactory;
        $this->messageValidator = $messageValidator;
        $this->serializer = $serializer;
    }

    public function getService(
        ClientInterface $httpClient,
        SchemaFactoryInterface $schemaFactory,
        string $schemaFile,
        LoggerInterface $logger = null,
        PaginationInterface $pagination = null,
        array $config = []
    ): ApiService {
        return new ApiService(
            $this->requestFactory,
            $this->messageValidator,
            $this->serializer,
            $httpClient,
            $schemaFactory->createSchema($schemaFile),
            $logger,
            $pagination,
            $config
        );
    }
}
