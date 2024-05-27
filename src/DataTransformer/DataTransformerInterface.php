<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\DataTransformer;

interface DataTransformerInterface
{
    public function support(string $type): bool;

    /**
     * @param array<string, mixed> $data
     *
     * @return array<int, array<string, mixed>>
     */
    public function transform(array $data): array;
}
