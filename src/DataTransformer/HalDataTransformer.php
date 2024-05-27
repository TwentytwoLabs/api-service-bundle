<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\DataTransformer;

final class HalDataTransformer implements DataTransformerInterface
{
    public function support(string $type): bool
    {
        return 1 === preg_match('#hal#', $type);
    }

    /**
     * @param array<int|string, mixed> $data
     *
     * @return array<int|string, mixed>
     */
    public function transform(array $data): array
    {
        return array_map(function ($data) {
            $relations = $this->removeEmbedded($data['_embedded'] ?? []);
            unset($data['_links'], $data['_embedded']);

            return array_merge($relations, $data);
        }, $data['_embedded']['item'] ?? []);
    }

    /**
     * @param array<int|string, mixed> $items
     *
     * @return array<int|string, mixed>
     */
    private function removeEmbedded(array $items): array
    {
        unset($items['_links']);
        foreach ($items as &$item) {
            if (is_array($item)) {
                $item = $this->removeEmbedded(array_merge($item, $item['_embedded'] ?? []));
                unset($item['_embedded'], $item['_links']);
            }
        }

        return $items;
    }
}
