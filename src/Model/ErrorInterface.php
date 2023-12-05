<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Model;

interface ErrorInterface
{
    public function getCode(): int;

    public function getMessage(): string;

    public function getViolations(): array;
}
