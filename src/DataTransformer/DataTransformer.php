<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\DataTransformer;

class DataTransformer
{
    /** @var DataTransformerInterface[] */
    private array $dataTransformers;

    public function __construct(array $dataTransformers)
    {
        $this->dataTransformers = $dataTransformers;
    }

    public function transform(string $type, array $data): array
    {
        foreach ($this->dataTransformers as $dataTransformer) {
            if ($dataTransformer->support($type)) {
                return $dataTransformer->transform($data);
            }
        }

        return $data;
    }
}
