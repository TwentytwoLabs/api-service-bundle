<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Pagination;

use Psr\Http\Message\ResponseInterface;
use TwentytwoLabs\Api\Definition\ResponseDefinition;
use TwentytwoLabs\Api\Service\Pagination\Pagination;
use TwentytwoLabs\Api\Service\Pagination\Provider\PaginationProviderInterface;

/**
 * Class PaginationProviderChain.
 */
class PaginationProviderChain implements PaginationProviderInterface
{
    /**
     * @var PaginationProviderInterface[]
     */
    private array $providers;
    private ?PaginationProviderInterface $matchedProvider;

    public function __construct(array $providers)
    {
        $this->providers = [];
        $this->matchedProvider = null;
        foreach ($providers as $provider) {
            $this->addProvider($provider);
        }
    }

    public function getPagination(array &$data, ResponseInterface $response, ResponseDefinition $responseDefinition): Pagination
    {
        if (null === $this->matchedProvider) {
            throw new \LogicException('No pagination provider available');
        }

        $pagination = $this->matchedProvider->getPagination($data, $response, $responseDefinition);
        // reset matched provider
        $this->matchedProvider = null;

        return $pagination;
    }

    public function supportPagination(array $data, ResponseInterface $response, ResponseDefinition $responseDefinition): bool
    {
        foreach ($this->providers as $index => $provider) {
            if ($provider->supportPagination($data, $response, $responseDefinition)) {
                $this->matchedProvider = $provider;

                return true;
            }
        }

        return false;
    }

    private function addProvider(PaginationProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }
}
