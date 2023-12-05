<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Denormalizer;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use TwentytwoLabs\ApiValidator\Definition\ResponseDefinition;
use TwentytwoLabs\ApiServiceBundle\DataTransformer\DataTransformer;
use TwentytwoLabs\ApiServiceBundle\Model\Collection;
use TwentytwoLabs\ApiServiceBundle\Model\Item;
use TwentytwoLabs\ApiServiceBundle\Model\ResourceInterface;
use TwentytwoLabs\ApiServiceBundle\Pagination\PaginationInterface;

final class ResourceDenormalizer implements DenormalizerInterface
{
    private DataTransformer $dataTransformer;

    public function __construct(DataTransformer $dataTransformer)
    {
        $this->dataTransformer = $dataTransformer;
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return ResourceInterface::class === $type;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): ResourceInterface
    {
        /** @var ResponseInterface $response */
        $response = $context['response'];

        /** @var RequestInterface $request */
        $request = $context['request'];

        /** @var ResponseDefinition $definition */
        $definition = $context['responseDefinition'];

        /** @var PaginationInterface|null $pagination */
        $pagination = $context['pagination'];

        if (!$definition->hasBodySchema()) {
            throw new \LogicException(sprintf('Cannot transform the response into a resource. You need to provide a schema for response %d in %s %s', $response->getStatusCode(), $request->getMethod(), $request->getUri()->getPath()));
        }

        $bodySchema = $this->getBodySchema($definition->getBodySchema(), $response->getHeaderLine('Content-Type'));
        if ('array' === $this->getSchemaType($bodySchema)) {
            if ($pagination?->support($response)) {
                $pagination = $pagination->getPagination($data, $response);
            }

            return new Collection(
                data: $this->dataTransformer->transform($response->getHeaderLine('Content-Type'), $data),
                meta: ['headers' => $response->getHeaders()],
                pagination: $pagination,
            );
        }

        return new Item(
            data: $this->dataTransformer->transform($response->getHeaderLine('Content-Type'), $data),
            meta: ['headers' => $response->getHeaders()]
        );
    }

    private function getSchemaType(array $schema): string
    {
        $type = $schema['x-type'] ?? $schema['type'] ?? null;

        if (null === $type) {
            throw new \RuntimeException('Cannot extract type from schema');
        }

        return $type;
    }

    private function getBodySchema(array $contentSchemata, string $responseContentType): array
    {
        $responseContentType = current(explode(';', $responseContentType));

        foreach ($contentSchemata as $contentSchema) {
            $regex = sprintf('#^%s#', str_replace(['/', '+'], ['\\/', '\+'], $responseContentType));

            if (1 === preg_match($regex, $responseContentType)) {
                return $contentSchema['schema'] ?? $contentSchemata;
            }
        }

        return [];
    }
}
