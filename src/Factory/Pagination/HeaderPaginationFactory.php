<?php

declare(strict_types=1);

namespace TwentytwoLabs\ApiServiceBundle\Factory\Pagination;

use Symfony\Component\OptionsResolver\OptionsResolver;
use TwentytwoLabs\ApiServiceBundle\Pagination\HeaderPagination;
use TwentytwoLabs\ApiServiceBundle\Pagination\PaginationInterface;

final class HeaderPaginationFactory extends AbstractPaginationFactory
{
    public function createPagination(string $name, array $options = []): PaginationInterface
    {
        return new HeaderPagination($this->validate($name, $options));
    }

    protected function configureOptionResolver(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            PaginationInterface::TOTAL_PAGES => 'X-Total-Items',
            PaginationInterface::TOTAL_ITEMS => 'X-Total-Pages',
            PaginationInterface::PAGE => 'X-Page',
            PaginationInterface::PER_PAGE => 'X-Per-Page',
        ]);
    }
}
