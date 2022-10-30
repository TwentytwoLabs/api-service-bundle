<?php

namespace TwentytwoLabs\ApiServiceBundle\Tests\Factory;

use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Http\Message\UriFactory;
use JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use Rize\UriTemplate;
use Symfony\Component\Serializer\SerializerInterface;
use TwentytwoLabs\Api\Decoder\DecoderInterface;
use TwentytwoLabs\Api\Factory\SchemaFactoryInterface;
use TwentytwoLabs\Api\Schema;
use TwentytwoLabs\Api\Service\ApiService;
use TwentytwoLabs\ApiServiceBundle\Factory\ServiceFactory;

class ServiceFactoryTest extends TestCase
{
    public function testShouldBuildAnApiService()
    {
        $aSchemaFile = 'schema.json';

        $uri = $this->createMock(UriInterface::class);

        $uriFactory = $this->createMock(UriFactory::class);
        $uriFactory->expects($this->once())->method('createUri')->with('http://domain.tld')->willReturn($uri);

        $uriTemplate = $this->createMock(UriTemplate::class);
        $messageFactory = $this->createMock(MessageFactory::class);
        $validator = $this->createMock(Validator::class);
        $serializer = $this->createMock(SerializerInterface::class);
        $decoder = $this->createMock(DecoderInterface::class);

        $factory = new ServiceFactory($uriFactory, $uriTemplate, $messageFactory, $validator, $serializer, $decoder);

        $httpClient = $this->createMock(HttpClient::class);

        $schema = $this->createMock(Schema::class);
        $schema->expects($this->exactly(2))->method('getSchemes')->willReturn(['http']);
        $schema->expects($this->once())->method('getHost')->willReturn('domain.tld');

        $schemaFactory = $this->createMock(SchemaFactoryInterface::class);
        $schemaFactory->expects($this->once())->method('createSchema')->with($aSchemaFile)->willReturn($schema);

        $this->assertInstanceOf(
            ApiService::class,
            $factory->getService($httpClient, $schemaFactory, $aSchemaFile, null, [])
        );
    }
}
