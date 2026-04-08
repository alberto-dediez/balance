<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\FoodLoss\Domain\Model\Packaging\Packaging;
use App\FoodLoss\Domain\Model\Packaging\PackagingFilter;
use App\FoodLoss\Domain\Model\Packaging\PackagingId;
use App\FoodLoss\Domain\Model\Packaging\PackagingRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryPackagingRepository implements PackagingRepository
{
    /** @param array<string, Packaging> $packagings */
    public function __construct(
        private array $packagings = [],
    ) {}

    public function add(Packaging $packaging): void
    {
        $this->packagings[(string) $packaging->id()] = $packaging;
    }

    public function remove(Packaging $packaging): void
    {
        if (!isset($this->packagings[(string) $packaging->id()])) {
            return;
        }

        unset($this->packagings[(string) $packaging->id()]);
    }

    public function find(PackagingId $packagingId): ?Packaging
    {
        return (isset($this->packagings[(string) $packagingId]) && false == $this->packagings[(string) $packagingId]->isRemoved())
            ? $this->packagings[(string) $packagingId]
            : null
        ;
    }

    /** @return array<int, Packaging> */
    public function findByFilter(PackagingFilter $filter): array
    {
        $results = [];

        if (0 === count($this->packagings)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->packagings as $packaging) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $packaging->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (
                $filter->accountId instanceof AccountId
                && (null == $packaging->accountId()
                    || false === $filter->accountId->equals($packaging->accountId()))
            ) {
                continue;
            }

            if (true == $packaging->isRemoved()) {
                continue;
            }

            if (
                $filter->propertyId instanceof PropertyId
                && !$packaging->stocks()->has($filter->propertyId)
            ) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $packaging;
        }

        return $results;
    }

    public function totalResultsByFilter(PackagingFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 0;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
