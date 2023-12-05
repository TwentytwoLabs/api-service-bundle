<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Denormalizer;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use TwentytwoLabs\ApiServiceBundle\Model\Error;
use TwentytwoLabs\ApiServiceBundle\Model\ErrorInterface;

final class ErrorDenormalizer implements DenormalizerInterface
{
    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return ErrorInterface::class === $type;
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = [])
    {
        /** @var ResponseInterface $response */
        $response = $context['response'];

        return new Error($response->getStatusCode(), $response->getReasonPhrase(), $data['violations'] ?? []);
    }
}
