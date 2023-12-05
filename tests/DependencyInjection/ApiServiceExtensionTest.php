<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Definition;
use TwentytwoLabs\ApiServiceBundle\DependencyInjection\ApiServiceExtension;

final class ApiServiceExtensionTest extends AbstractExtensionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setParameter('kernel.debug', true);
    }

    protected function getContainerExtensions(): array
    {
        return [new ApiServiceExtension()];
    }

    public function testShouldLoadDefaultConfigs(): void
    {
        $this->load();

        $defaultServices = [
            'client' => ClientInterface::class,
            'request_factory' => RequestFactoryInterface::class,
            'uri_factory' => UriFactoryInterface::class,
            'stream_factory' => StreamFactoryInterface::class,
            'serializer' => 'serializer',
        ];

        foreach ($defaultServices as $type => $service) {
            $this->assertContainerBuilderHasAlias(sprintf('api_service.%s', $type), $service);
        }
        $this->assertContainerBuilderHasService('api_service.data_transformer');
        $this->assertContainerBuilderHasService('api_service.data_transformer.hal');
        $this->assertContainerBuilderHasService('api_service.factory.pagination.hal');
        $this->assertContainerBuilderHasService('api_service.factory.pagination.header');
        $this->assertContainerBuilderHasService('api_service.serializer.decoder.symfony');
        $this->assertContainerBuilderHasService('api_service.serializer.decoder');
        $this->assertContainerBuilderHasService('api_service.denormalizer.resource');
        $this->assertContainerBuilderHasService('api_service.denormalizer.error');
        $this->assertContainerBuilderHasService('api_service.uri_template');
        $this->assertContainerBuilderHasService('api_service.factory.request');
        $this->assertContainerBuilderHasService('api_service.schema_factory.open-api');
        $this->assertContainerBuilderHasService('api_service.schema_factory.cached_factory');
        $this->assertContainerBuilderHasService('api_service.factory');
        $this->assertContainerBuilderHasService('api_service.validator.json_schema_validator');
        $this->assertContainerBuilderHasService('api_service.validator.message');
    }

    public function testShouldProvideApiServicesWithoutPaginationAndWithoutCache(): void
    {
        $this->load([
            'apis' => [
                'foo' => [
                    'schema' => '/path/to/schema.json',
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService('api_service.api.foo');

        $definition = $this->container->findDefinition('api_service.api.foo');
        $this->assertSame('api_service.factory', (string) $definition->getFactory()[0]);
        $this->assertSame('getService', (string) $definition->getFactory()[1]);

        $this->assertSame('api_service.client', (string) $definition->getArgument(0));
        $this->assertSame('api_service.schema_factory.open-api', (string) $definition->getArgument(1));
        $this->assertSame('/path/to/schema.json', (string) $definition->getArgument(2));
        $this->assertSame('logger', (string) $definition->getArgument(3));
        $this->assertNull($definition->getArgument(4));
        $this->assertSame(
            ['validateRequest' => true, 'validateResponse' => true, 'returnResponse' => false],
            $definition->getArgument(5)
        );

        $definition = $this->container->findDefinition('api_service.api.foo');
        $this->assertSame('api_service.factory', (string) $definition->getFactory()[0]);
        $this->assertSame('getService', (string) $definition->getFactory()[1]);

        $this->assertContainerBuilderHasAlias('TwentytwoLabs\ApiServiceBundle\ApiService $foo');
        $alias = $this->container->getAlias('TwentytwoLabs\ApiServiceBundle\ApiService $foo');
        $this->assertInstanceOf(Alias::class, $alias);
        $this->assertSame('api_service.api.foo', (string) $alias);
    }

    public function testShouldProvideApiServicesWithoutPaginationAndWithoutCacheForSwagger(): void
    {
        $this->load([
            'apis' => [
                'foo' => [
                    'schema' => '/path/to/schema.json',
                    'version' => 2,
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService('api_service.api.foo');

        $definition = $this->container->findDefinition('api_service.api.foo');
        $this->assertSame('api_service.factory', (string) $definition->getFactory()[0]);
        $this->assertSame('getService', (string) $definition->getFactory()[1]);

        $this->assertSame('api_service.client', (string) $definition->getArgument(0));
        $this->assertSame('api_service.schema_factory.swagger', (string) $definition->getArgument(1));
        $this->assertSame('/path/to/schema.json', (string) $definition->getArgument(2));
        $this->assertSame('logger', (string) $definition->getArgument(3));
        $this->assertNull($definition->getArgument(4));
        $this->assertSame(
            ['validateRequest' => true, 'validateResponse' => true, 'returnResponse' => false],
            $definition->getArgument(5)
        );

        $definition = $this->container->findDefinition('api_service.api.foo');
        $this->assertSame('api_service.factory', (string) $definition->getFactory()[0]);
        $this->assertSame('getService', (string) $definition->getFactory()[1]);

        $this->assertContainerBuilderHasAlias('TwentytwoLabs\ApiServiceBundle\ApiService $foo');
        $alias = $this->container->getAlias('TwentytwoLabs\ApiServiceBundle\ApiService $foo');
        $this->assertInstanceOf(Alias::class, $alias);
        $this->assertSame('api_service.api.foo', (string) $alias);
    }

    public function testShouldProvideApiServicesWithPaginationAndWithoutCache(): void
    {
        $this->load([
            'apis' => [
                'foo' => [
                    'schema' => '/path/to/schema.json',
                    'pagination' => [
                        'factory' => 'api_service.factory.pagination.header',
                        'options' => [],
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService('api_service.api.foo');

        $definition = $this->container->findDefinition('api_service.api.foo');
        $this->assertSame('api_service.factory', (string) $definition->getFactory()[0]);
        $this->assertSame('getService', (string) $definition->getFactory()[1]);

        $this->assertSame('api_service.client', (string) $definition->getArgument(0));
        $this->assertSame('api_service.schema_factory.open-api', (string) $definition->getArgument(1));
        $this->assertSame('/path/to/schema.json', (string) $definition->getArgument(2));
        $this->assertSame('logger', (string) $definition->getArgument(3));

        $paginationRef = $definition->getArgument(4);

        $this->assertInstanceOf(Definition::class, $paginationRef);
        $this->assertSame('api_service.factory.pagination.header', (string) $paginationRef->getFactory()[0]);
        $this->assertSame('createPagination', $paginationRef->getFactory()[1]);
        $this->assertSame('foo', (string) $paginationRef->getArgument(0));
        $this->assertSame([], $paginationRef->getArgument(1));
        $this->assertFalse($paginationRef->isPublic());

        $this->assertSame(
            ['validateRequest' => true, 'validateResponse' => true, 'returnResponse' => false],
            $definition->getArgument(5)
        );

        $definition = $this->container->findDefinition('api_service.api.foo');
        $this->assertSame('api_service.factory', (string) $definition->getFactory()[0]);
        $this->assertSame('getService', (string) $definition->getFactory()[1]);

        $this->assertContainerBuilderHasAlias('TwentytwoLabs\ApiServiceBundle\ApiService $foo');
        $alias = $this->container->getAlias('TwentytwoLabs\ApiServiceBundle\ApiService $foo');
        $this->assertInstanceOf(Alias::class, $alias);
        $this->assertSame('api_service.api.foo', (string) $alias);
    }

    public function testShouldProvideApiServicesWithPaginationAndWithoutCacheForSwagger(): void
    {
        $this->load([
            'apis' => [
                'foo' => [
                    'schema' => '/path/to/schema.json',
                    'version' => 2,
                    'pagination' => [
                        'factory' => 'api_service.factory.pagination.header',
                        'options' => [],
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService('api_service.api.foo');

        $definition = $this->container->findDefinition('api_service.api.foo');
        $this->assertSame('api_service.factory', (string) $definition->getFactory()[0]);
        $this->assertSame('getService', (string) $definition->getFactory()[1]);

        $this->assertSame('api_service.client', (string) $definition->getArgument(0));
        $this->assertSame('api_service.schema_factory.swagger', (string) $definition->getArgument(1));
        $this->assertSame('/path/to/schema.json', (string) $definition->getArgument(2));
        $this->assertSame('logger', (string) $definition->getArgument(3));

        $paginationRef = $definition->getArgument(4);

        $this->assertInstanceOf(Definition::class, $paginationRef);
        $this->assertSame('api_service.factory.pagination.header', (string) $paginationRef->getFactory()[0]);
        $this->assertSame('createPagination', $paginationRef->getFactory()[1]);
        $this->assertSame('foo', (string) $paginationRef->getArgument(0));
        $this->assertSame([], $paginationRef->getArgument(1));
        $this->assertFalse($paginationRef->isPublic());

        $this->assertSame(
            ['validateRequest' => true, 'validateResponse' => true, 'returnResponse' => false],
            $definition->getArgument(5)
        );

        $definition = $this->container->findDefinition('api_service.api.foo');
        $this->assertSame('api_service.factory', (string) $definition->getFactory()[0]);
        $this->assertSame('getService', (string) $definition->getFactory()[1]);

        $this->assertContainerBuilderHasAlias('TwentytwoLabs\ApiServiceBundle\ApiService $foo');
        $alias = $this->container->getAlias('TwentytwoLabs\ApiServiceBundle\ApiService $foo');
        $this->assertInstanceOf(Alias::class, $alias);
        $this->assertSame('api_service.api.foo', (string) $alias);
    }

    public function testShouldProvideApiServicesWithCache(): void
    {
        $this->load([
            'apis' => [
                'foo' => [
                    'schema' => '/path/to/schema.json',
                    'cache' => [
                        'enabled' => true,
                        'service' => 'my.psr6_cache_impl',
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService('api_service.api.foo');

        $definition = $this->container->findDefinition('api_service.api.foo');
        $this->assertSame('api_service.factory', (string) $definition->getFactory()[0]);
        $this->assertSame('getService', (string) $definition->getFactory()[1]);

        $this->assertSame('api_service.client', (string) $definition->getArgument(0));
        $this->assertSame('api_service.schema_factory.cached_factory', (string) $definition->getArgument(1));
        $this->assertSame('/path/to/schema.json', (string) $definition->getArgument(2));
        $this->assertSame('logger', (string) $definition->getArgument(3));
        $this->assertNull($definition->getArgument(4));
        $this->assertSame(
            ['validateRequest' => true, 'validateResponse' => true, 'returnResponse' => false],
            $definition->getArgument(5)
        );

        $this->assertContainerBuilderHasAlias('TwentytwoLabs\ApiServiceBundle\ApiService $foo');
        $alias = $this->container->getAlias('TwentytwoLabs\ApiServiceBundle\ApiService $foo');
        $this->assertInstanceOf(Alias::class, $alias);
        $this->assertSame('api_service.api.foo', (string) $alias);

        $cachedFactory = $this->container->findDefinition('api_service.schema_factory.cached_factory');
        $this->assertSame('my.psr6_cache_impl', (string) $cachedFactory->getArgument(0));
        $this->assertSame('api_service.schema_factory.open-api', (string) $cachedFactory->getArgument(1));
    }

    public function testShouldProvideApiServicesWithCacheForSwagger(): void
    {
        $this->load([
            'apis' => [
                'foo' => [
                    'schema' => '/path/to/schema.json',
                    'version' => 2,
                    'cache' => [
                        'enabled' => true,
                        'service' => 'my.psr6_cache_impl',
                    ],
                ],
            ],
        ]);

        $this->assertContainerBuilderHasService('api_service.api.foo');

        $definition = $this->container->findDefinition('api_service.api.foo');
        $this->assertSame('api_service.factory', (string) $definition->getFactory()[0]);
        $this->assertSame('getService', (string) $definition->getFactory()[1]);

        $this->assertSame('api_service.client', (string) $definition->getArgument(0));
        $this->assertSame('api_service.schema_factory.cached_factory', (string) $definition->getArgument(1));
        $this->assertSame('/path/to/schema.json', (string) $definition->getArgument(2));
        $this->assertSame('logger', (string) $definition->getArgument(3));
        $this->assertNull($definition->getArgument(4));
        $this->assertSame(
            ['validateRequest' => true, 'validateResponse' => true, 'returnResponse' => false],
            $definition->getArgument(5)
        );

        $this->assertContainerBuilderHasAlias('TwentytwoLabs\ApiServiceBundle\ApiService $foo');
        $alias = $this->container->getAlias('TwentytwoLabs\ApiServiceBundle\ApiService $foo');
        $this->assertInstanceOf(Alias::class, $alias);
        $this->assertSame('api_service.api.foo', (string) $alias);

        $cachedFactory = $this->container->findDefinition('api_service.schema_factory.cached_factory');
        $this->assertSame('my.psr6_cache_impl', (string) $cachedFactory->getArgument(0));
        $this->assertSame('api_service.schema_factory.swagger', (string) $cachedFactory->getArgument(1));
    }
}
