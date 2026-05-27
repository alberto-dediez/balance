<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Persistence\InMemory\Repository;

use App\FoodLoss\Domain\Model\WasteManager\WasteManager;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerFilter;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerId;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryWasteManagerRepository implements WasteManagerRepository
{
    /** @param array<string, WasteManager> $wasteManagers */
    public function __construct(
        private array $wasteManagers = [],
    ) {}

    public function add(WasteManager $wasteManager): void
    {
        if (isset($this->wasteManagers[(string) $wasteManager->id()])) {
            return;
        }

        $this->wasteManagers[(string) $wasteManager->id()] = $wasteManager;
    }

    public function remove(WasteManager $wasteManager): void
    {
        if (!isset($this->wasteManagers[(string) $wasteManager->id()])) {
            return;
        }

        unset($this->wasteManagers[(string) $wasteManager->id()]);
    }

    public function find(WasteManagerId $wasteManagerId): ?WasteManager
    {
        return (isset($this->wasteManagers[(string) $wasteManagerId]) && false == $this->wasteManagers[(string) $wasteManagerId]->isRemoved())
            ? $this->wasteManagers[(string) $wasteManagerId]
            : null
        ;
    }

    /** @return array<int, WasteManager> */
    public function findByFilter(WasteManagerFilter $filter): array
    {
        $results = [];

        if (0 === count($this->wasteManagers)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->wasteManagers as $wasteManager) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $wasteManager->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (null !== $filter->accountId && !$wasteManager->accountId()?->equals($filter->accountId)) {
                continue;
            }

            if (null !== $filter->animalFeedAuthorized && $wasteManager->animalFeedAuthorized() !== $filter->animalFeedAuthorized) {
                continue;
            }

            if (null !== $filter->compostAuthorized && $wasteManager->compostAuthorized() !== $filter->compostAuthorized) {
                continue;
            }

            if (true == $wasteManager->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $wasteManager;
        }

        return $results;
    }

    public function totalResultsByFilter(WasteManagerFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
