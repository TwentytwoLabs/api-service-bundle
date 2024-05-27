<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Model;

interface ResourceInterface
{
    /**
     * @return array<int|string, mixed>
     */
    public function getData(): array;

    /**
     * @return array<int|string, mixed>
     */
    public function getMeta(): array;
}
