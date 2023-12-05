<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Tests\Factory;

use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\Serializer\SerializerInterface;
use TwentytwoLabs\ApiValidator\Factory\SchemaFactoryInterface;
use TwentytwoLabs\ApiValidator\Schema;
use TwentytwoLabs\ApiValidator\Validator\MessageValidator;
use TwentytwoLabs\ApiServiceBundle\ApiService;
use TwentytwoLabs\ApiServiceBundle\Factory\ApiServiceFactory;
use TwentytwoLabs\ApiServiceBundle\Factory\RequestFactory;

final class ApiServiceFactoryTest extends TestCase
{
    private RequestFactory $requestFactory;
    private MessageValidator $messageValidator;
    private SerializerInterface $serializer;

    protected function setUp(): void
    {
        $this->requestFactory = $this->createMock(RequestFactory::class);
        $this->messageValidator = $this->createMock(MessageValidator::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
    }

    public function testShouldBuildAnApiService(): void
    {
        $schemaFile = 'schema.json';

        $schema = $this->createMock(Schema::class);

        $schemaFactory = $this->createMock(SchemaFactoryInterface::class);
        $schemaFactory->expects($this->once())->method('createSchema')->with($schemaFile)->willReturn($schema);

        $httpClient = $this->createMock(ClientInterface::class);

        $factory = $this->getFactory();
        $this->assertInstanceOf(ApiService::class, $factory->getService($httpClient, $schemaFactory, $schemaFile));
    }

    private function getFactory(): ApiServiceFactory
    {
        return new ApiServiceFactory($this->requestFactory, $this->messageValidator, $this->serializer);
    }
}
