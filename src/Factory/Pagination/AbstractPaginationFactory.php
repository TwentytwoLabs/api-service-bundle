<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Factory\Pagination;

use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractPaginationFactory implements PaginationFactoryInterface
{
    /**
     * @param array<int|string, mixed> $options
     *
     * @return array<int|string, mixed>
     * @throws \Exception
     */
    protected function validate(string $name, array $options): array
    {
        $resolver = new OptionsResolver();
        $this->configureOptionResolver($resolver);

        try {
            return $resolver->resolve($options);
        } catch (\Exception $e) {
            $message = sprintf(
                'Error while configure pagination %s. Verify your configuration at "%s". %s',
                $name,
                sprintf('twenty-two-labs.api_service.%s.pagination.options', $name),
                $e->getMessage()
            );

            throw new \Exception($message, $e->getCode(), $e);
        }
    }

    abstract protected function configureOptionResolver(OptionsResolver $resolver): void;
}
