<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseService;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceFilter;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryPurchaseServiceRepository implements PurchaseServiceRepository
{
    /** @param array<string, PurchaseService> $purchaseServices */
    public function __construct(
        private array $purchaseServices = [],
    ) {}

    public function add(PurchaseService $purchaseService): void
    {
        if (isset($this->purchaseServices[(string) $purchaseService->id()])) {
            return;
        }

        $this->purchaseServices[(string) $purchaseService->id()] = $purchaseService;
    }

    public function remove(PurchaseService $purchaseService): void
    {
        if (!isset($this->purchaseServices[(string) $purchaseService->id()])) {
            return;
        }

        unset($this->purchaseServices[(string) $purchaseService->id()]);
    }

    public function find(PurchaseServiceId $purchaseServiceId): ?PurchaseService
    {
        return (isset($this->purchaseServices[(string) $purchaseServiceId]) && false == $this->purchaseServices[(string) $purchaseServiceId]->isRemoved())
            ? $this->purchaseServices[(string) $purchaseServiceId]
            : null
        ;
    }

    /** @return array<int, PurchaseService> */
    public function findByFilter(PurchaseServiceFilter $filter): array
    {
        $results = [];

        if (0 === count($this->purchaseServices)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        $way = 'ASC' === $filter->orderWay ? 1 : -1;
        $purchaseServices = array_values($this->purchaseServices);
        usort(
            $purchaseServices,
            static fn(PurchaseService $a, PurchaseService $b): int => $way * match ($filter->orderBy) {
                'registeredAt' => $a->registeredAt() <=> $b->registeredAt(),
                'updatedAt' => $a->updatedAt() <=> $b->updatedAt(),
                default => $a->name() <=> $b->name(),
            },
        );

        foreach ($purchaseServices as $purchaseService) {
            if (
                !empty($filter->name)
                && !str_contains(strtolower((string) $purchaseService->name()), strtolower($filter->name))
            ) {
                continue;
            }

            if (null !== $filter->category && $purchaseService->category() !== $filter->category) {
                continue;
            }

            if (true == $purchaseService->isRemoved()) {
                continue;
            }

            if ($offset > 0) {
                --$offset;

                continue;
            }

            if (count($results) >= $filter->limit && $filter->limit > 0) {
                continue;
            }

            $results[] = $purchaseService;
        }

        return $results;
    }

    public function totalResultsByFilter(PurchaseServiceFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($clonedFilter));
    }
}
