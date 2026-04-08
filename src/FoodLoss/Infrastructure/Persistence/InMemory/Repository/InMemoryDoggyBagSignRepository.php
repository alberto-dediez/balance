<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSign;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignFilter;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignId;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryDoggyBagSignRepository implements DoggyBagSignRepository
{
    /** @param array<string, DoggyBagSign> $doggyBagSigns */
    public function __construct(
        private array $doggyBagSigns = [],
    ) {}

    public function add(DoggyBagSign $doggyBagSign): void
    {
        $this->doggyBagSigns[(string) $doggyBagSign->id()] = $doggyBagSign;
    }

    public function remove(DoggyBagSign $doggyBagSign): void
    {
        if (!isset($this->doggyBagSigns[(string) $doggyBagSign->id()])) {
            return;
        }

        unset($this->doggyBagSigns[(string) $doggyBagSign->id()]);
    }

    public function find(DoggyBagSignId $doggyBagSignId): ?DoggyBagSign
    {
        return (isset($this->doggyBagSigns[(string) $doggyBagSignId]) && false == $this->doggyBagSigns[(string) $doggyBagSignId]->isRemoved())
            ? $this->doggyBagSigns[(string) $doggyBagSignId]
            : null
        ;
    }

    /** @return array<int, DoggyBagSign> */
    public function findByFilter(DoggyBagSignFilter $filter): array
    {
        $results = [];

        if (0 === count($this->doggyBagSigns)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->doggyBagSigns as $doggyBagSign) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower($doggyBagSign->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if ($filter->accountId instanceof AccountId) {
                $doggyBagSignAccountId = $doggyBagSign->accountId();
                if (null === $doggyBagSignAccountId || !$filter->accountId->equals($doggyBagSignAccountId)) {
                    continue;
                }
            }

            if ($filter->propertyId instanceof PropertyId) {
                $doggyBagSignPropertyId = $doggyBagSign->propertyId();
                if (null === $doggyBagSignPropertyId || !$filter->propertyId->equals($doggyBagSignPropertyId)) {
                    continue;
                }
            }

            if (true == $doggyBagSign->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $doggyBagSign;
        }

        return $results;
    }

    public function totalResultsByFilter(DoggyBagSignFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
