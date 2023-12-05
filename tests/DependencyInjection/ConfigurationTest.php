<?php

namespace TwentytwoLabs\ApiServiceBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionConfigurationTestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use TwentytwoLabs\ApiServiceBundle\DependencyInjection\ApiServiceExtension;
use TwentytwoLabs\ApiServiceBundle\DependencyInjection\Configuration;

final class ConfigurationTest extends AbstractExtensionConfigurationTestCase
{
    public function testEmptyConfiguration(): void
    {
        $expectedEmptyConfig = [
            'default_services' => [
                'client' => ClientInterface::class,
                'uri_factory' => UriFactoryInterface::class,
                'request_factory' => RequestFactoryInterface::class,
                'stream_factory' => StreamFactoryInterface::class,
                'serializer' => 'serializer',
            ],
            'apis' => [],
        ];

        $this->assertProcessedConfigurationEquals(
            $expectedEmptyConfig,
            [__DIR__.'/../Resources/Fixtures/config/empty.yml']
        );
    }

    public function testSupportsAllConfigFormats(): void
    {
        $expectedConfiguration = [
            'default_services' => [
                'client' => 'httplug.client.acme',
                'uri_factory' => 'my.uri_factory',
                'request_factory' => 'my.request_factory',
                'stream_factory' => 'my.stream_factory',
                'serializer' => 'serializer',
            ],
            'apis' => [
                'foo' => [
                    'schema' => '/path/to/foo.yml',
                    'client' => 'api_service.client',
                    'logger' => 'my.logger',
                    'cache' => [
                        'enabled' => true,
                        'service' => 'my.psr6_cache_impl',
                    ],
                    'pagination' => [
                        'factory' => 'api_service.factory.pagination.header',
                        'options' => [],
                    ],
                    'config' => [
                        'validateRequest' => true,
                        'validateResponse' => true,
                        'returnResponse' => false,
                        'baseUri' => 'https://foo.com',
                    ],
                    'version' => 3,
                ],
                'bar' => [
                    'schema' => '/path/to/bar.json',
                    'client' => 'httplug.client.bar',
                    'logger' => 'logger',
                    'cache' => [
                        'enabled' => true,
                        'service' => 'my.psr6_cache_impl',
                    ],
                    'pagination' => [
                        'factory' => 'api_service.factory.pagination.header',
                        'options' => [],
                    ],
                    'config' => [
                        'validateRequest' => true,
                        'validateResponse' => true,
                        'returnResponse' => false,
                        'baseUri' => 'https://bar.com',
                    ],
                    'version' => 3,
                ],
            ],
        ];

        $fixturesPath = __DIR__.'/../Resources/Fixtures';

        $this->assertProcessedConfigurationEquals(
            $expectedConfiguration,
            [$fixturesPath.'/config/full.yml']
        );
    }

    protected function getContainerExtension(): ApiServiceExtension
    {
        return new ApiServiceExtension();
    }

    protected function getConfiguration(): Configuration
    {
        return new Configuration();
    }
}
