<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\DataTransformer;

interface DataTransformerInterface
{
    public function support(string $type): bool;

    public function transform(array $data): array;
}
