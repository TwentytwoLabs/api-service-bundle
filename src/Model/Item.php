<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Model;

final class Item implements ResourceInterface
{
    /** @var array<int|string, mixed> */
    private array $data;
    /** @var array<int|string, mixed> */
    private array $meta;

    /**
     * @param array<int|string, mixed> $data
     * @param array<int|string, mixed> $meta
     */
    public function __construct(array $data, array $meta)
    {
        $this->data = $data;
        $this->meta = $meta;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }
}
