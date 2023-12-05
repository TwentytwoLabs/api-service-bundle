<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Model;

class Item implements ResourceInterface
{
    private array $data;
    private array $meta;

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
