<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Factory;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Rize\UriTemplate;
use Symfony\Component\Serializer\SerializerInterface;
use TwentytwoLabs\ApiValidator\Decoder\DecoderUtils;
use TwentytwoLabs\ApiValidator\Definition\OperationDefinition;
use TwentytwoLabs\ApiValidator\Definition\Parameters;

class RequestFactory
{
    private RequestFactoryInterface $requestFactory;
    private UriTemplate $uriTemplate;
    private UriFactoryInterface $uriFactory;
    private StreamFactoryInterface $streamFactory;
    private SerializerInterface $serializer;

    public function __construct(
        RequestFactoryInterface $requestFactory,
        UriTemplate $uriTemplate,
        UriFactoryInterface $uriFactory,
        StreamFactoryInterface $streamFactory,
        SerializerInterface $serializer
    ) {
        $this->requestFactory = $requestFactory;
        $this->uriTemplate = $uriTemplate;
        $this->uriFactory = $uriFactory;
        $this->streamFactory = $streamFactory;
        $this->serializer = $serializer;
    }

    public function createRequestFromDefinition(OperationDefinition $definition, string $baseUri, array $params): RequestInterface
    {
        $requestParameters = $definition->getRequestParameters();
        $parameters = 'PATCH' !== $definition->getMethod() ? $this->getDefaultValues($requestParameters) : [];

        foreach ($params as $name => $value) {
            $requestParameter = $requestParameters->getByName($name);
            if (null === $requestParameter) {
                continue;
            }

            if (\in_array($requestParameter->getLocation(), ['body', 'formData'])) {
                $parameters[$requestParameter->getLocation()] = array_merge(
                    $parameters[$requestParameter->getLocation()],
                    $value
                );
            } else {
                $parameters[$requestParameter->getLocation()][$name] = $value;
            }
        }

        if (!empty($parameters['body'])) {
            $parameters['body'] = $this->serializer->serialize(
                $parameters['body'],
                DecoderUtils::extractFormatFromContentType($parameters['header']['content-type'])
            );
        }

        if (!empty($parameters['formData'])) {
            $parameters['body'] = implode('&', $parameters['formData']);
        }

        return $this->createRequest(
            $baseUri,
            $definition->getMethod(),
            $definition->getPathTemplate(),
            $parameters
        );
    }

    private function getDefaultValues(Parameters $requestParameters): array
    {
        $parameters = ['path' => [], 'query' => [], 'headers' => [], 'body' => null, 'formData' => []];

        foreach ($requestParameters as $name => $requestParameter) {
            $schema = $requestParameter->getSchema();

            if ('body' === $requestParameter->getLocation()) {
                $schema = $requestParameter->getSchema();

                $parameters['body'] = array_filter(array_map(function (array $params) {
                    return $params['default'] ?? null;
                }, $schema['properties']));
            } else {
                if (empty($schema['default'])) {
                    continue;
                }

                $parameters[$requestParameter->getLocation()][$name] = $schema['default'];
            }
        }

        return $parameters;
    }

    private function createRequest(string $baseUri, string $method, string $pathTemplate, array $parameters): RequestInterface
    {
        $path = $this->uriTemplate->expand($pathTemplate, $parameters['path']);
        $query = http_build_query($parameters['query']);

        $request = $this->requestFactory->createRequest(
            $method,
            $this->uriFactory->createUri($baseUri)->withPath($path)->withQuery($query)
        );

        foreach ($parameters['header'] as $name => $header) {
            $request = $request->withHeader($name, $header);
        }

        if (!empty($parameters['body'])) {
            $request = $request->withBody($this->streamFactory->createStream($parameters['body']));
        }

        return $request;
    }
}
