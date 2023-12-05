<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Model;

interface ResourceInterface
{
    public function getData(): array;

    public function getMeta(): array;
}
