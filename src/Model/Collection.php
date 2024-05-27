<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Model;

/**
 * @implements \IteratorAggregate<array>
 */
final class Collection implements ResourceInterface, \IteratorAggregate
{
    /** @var array<int|string, mixed> */
    private array $data;
    /** @var array<int|string, mixed> */
    private array $meta;
    protected ?Pagination $pagination;

    public function __construct(array $data, array $meta, Pagination $pagination = null)
    {
        $this->data = $data;
        $this->meta = $meta;
        $this->pagination = $pagination;
    }

    /**
     * @return \ArrayIterator<int|string, array<int|string, mixed>>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->getData());
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function hasPagination(): bool
    {
        return null !== $this->pagination;
    }

    public function getPagination(): ?Pagination
    {
        return $this->pagination;
    }
}
