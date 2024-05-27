<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Denormalizer;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use TwentytwoLabs\ApiServiceBundle\Model\Error;
use TwentytwoLabs\ApiServiceBundle\Model\ErrorInterface;

final class ErrorDenormalizer implements DenormalizerInterface
{
    /**
     * @param array<int|string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return ErrorInterface::class === $type;
    }

    /**
     * @param array<int|string, mixed> $context
     */
    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): mixed
    {
        /** @var ResponseInterface $response */
        $response = $context['response'];

        return new Error($response->getStatusCode(), $response->getReasonPhrase(), $data['violations'] ?? []);
    }

    /**
     * @return array<string, boolean>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            '*' => false,
            ErrorInterface::class => true,
        ];
    }
}
