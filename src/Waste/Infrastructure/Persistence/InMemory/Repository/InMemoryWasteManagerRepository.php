<?php

namespace App\Waste\Infrastructure\Persistence\InMemory\Repository;

use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\WasteManager\WasteManager;
use App\Waste\Domain\Model\WasteManager\WasteManagerFilter;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use App\Waste\Domain\Model\WasteManager\WasteManagerRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryWasteManagerRepository implements WasteManagerRepository
{
    public function __construct(private array $wasteManagers = [])
    {

    }

    public function add(WasteManager $wasteManager): void
    {
        if (isset($this->wasteManagers[(string) $wasteManager->id()])) {
            return ;
        }

        $this->wasteManagers[(string) $wasteManager->id()] = $wasteManager;
    }

    public function remove(WasteManager $wasteManager): void
    {
        if (!isset($this->wasteManagers[(string) $wasteManager->id()])) {
            return ;
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

    public function findByFilter(WasteManagerFilter $filter): array
    {
        $results = [];

        if (count($this->wasteManagers) === 0) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->wasteManagers as $wasteManager) {
            if (
                $filter->accountId instanceof AccountId
                && (
                    null == $wasteManager->accountId()
                    || false === $filter->accountId->equals($wasteManager->accountId())
                )
            ) {
                continue;
            }

            if (
                null !== $filter->name
                && (
                    $filter->name !== $wasteManager->name()
                )
            ) {
                continue;
            }

            if (
                null !== $filter->isPublic
                && (
                    $filter->isPublic !== $wasteManager->isPublic()
                )
            ) {
                continue;
            }

            if (true == $wasteManager->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                $offset--;

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

        return count($this->findByFilter($filter));
    }
}
