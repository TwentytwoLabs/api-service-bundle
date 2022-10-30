<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Factory;

use Http\Message\MessageFactory;
use Http\Message\UriFactory;
use JsonSchema\Validator;
use Psr\Http\Client\ClientInterface;
use Psr\Log\LoggerInterface;
use Rize\UriTemplate;
use Symfony\Component\Serializer\SerializerInterface;
use TwentytwoLabs\Api\Decoder\DecoderInterface;
use TwentytwoLabs\Api\Factory\SchemaFactoryInterface;
use TwentytwoLabs\Api\Service\ApiService;
use TwentytwoLabs\Api\Validator\MessageValidator;

class ServiceFactory
{
    private UriFactory $uriFactory;
    private UriTemplate $uriTemplate;
    private MessageFactory $messageFactory;
    private Validator $validator;
    private SerializerInterface $serializer;
    private DecoderInterface $decoder;

    public function __construct(
        UriFactory $uriFactory,
        UriTemplate $uriTemplate,
        MessageFactory $messageFactory,
        Validator $validator,
        SerializerInterface $serializer,
        DecoderInterface $decoder
    ) {
        $this->uriFactory = $uriFactory;
        $this->uriTemplate = $uriTemplate;
        $this->messageFactory = $messageFactory;
        $this->validator = $validator;
        $this->serializer = $serializer;
        $this->decoder = $decoder;
    }

    public function getService(
        ClientInterface $httpClient,
        SchemaFactoryInterface $schemaFactory,
        string $schemaFile,
        ?LoggerInterface $logger = null,
        array $config = []
    ): ApiService {
        $schema = $schemaFactory->createSchema($schemaFile);

        return new ApiService(
            $this->uriFactory,
            $this->uriTemplate,
            $httpClient,
            $this->messageFactory,
            $schema,
            new MessageValidator($this->validator, $this->decoder),
            $this->serializer,
            $logger,
            $config
        );
    }
}
