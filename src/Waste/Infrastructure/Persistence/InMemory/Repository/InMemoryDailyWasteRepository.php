<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Persistence\InMemory\Repository;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\DailyWaste\DailyWaste;
use App\Waste\Domain\Model\DailyWaste\DailyWasteFilter;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use App\Waste\Domain\Model\DailyWaste\DailyWasteRepository;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemoryDailyWasteRepository implements DailyWasteRepository
{
    public function __construct(
        private array $dailyWastes = [],
    ) {}

    public function add(DailyWaste $dailyWaste): void
    {
        if (isset($this->dailyWastes[(string) $dailyWaste->id()])) {
            return;
        }

        $this->dailyWastes[(string) $dailyWaste->id()] = $dailyWaste;
    }

    public function remove(DailyWaste $dailyWaste): void
    {
        if (!isset($this->dailyWastes[(string) $dailyWaste->id()])) {
            return;
        }

        unset($this->dailyWastes[(string) $dailyWaste->id()]);
    }

    public function find(DailyWasteId $dailyWasteId): ?DailyWaste
    {
        return $this->dailyWastes[(string) $dailyWasteId] ?? null
        ;
    }

    public function findByDay(\DateTimeImmutable $day, PropertyId $propertyId, WasteId $wasteId, Purpose $purpose, WasteManagerId $wasteManagerId, AccountId $accountId): ?DailyWaste
    {
        $filter = new DailyWasteFilter(
            properties: [$propertyId],
            wasteId: $wasteId,
            wasteManagerId: $wasteManagerId,
            purpose: $purpose,
            accountId: $accountId,
            day: $day,
            limit: 0,
        );

        $results = $this->findByFilter($filter);

        return 0 === count($results) ? null : $results[0];
    }

    public function findByFilter(DailyWasteFilter $filter): array
    {
        $results = [];

        if (0 === count($this->dailyWastes)) {
            return $results;
        }

        $offset = $filter->page * $filter->limit;

        foreach ($this->dailyWastes as $dailyWaste) {
            if (
                $filter->wasteId instanceof WasteId
                && (
                    null == $dailyWaste->wasteId()
                    || false === $filter->wasteId->equals($dailyWaste->wasteId())
                )
            ) {
                continue;
            }

            if ($filter->purpose instanceof Purpose && $filter->purpose !== $dailyWaste->purpose()) {
                continue;
            }

            if (
                $filter->wasteManagerId instanceof WasteManagerId
                && (
                    null == $dailyWaste->wasteManagerId()
                    || false === $filter->wasteManagerId->equals($dailyWaste->wasteManagerId())
                )
            ) {
                continue;
            }

            if (
                count($filter->properties) > 0
                && (
                    null == $dailyWaste->propertyId()
                    || false === array_reduce($filter->properties, fn(false $carry, $value) => $carry || $value->equals($dailyWaste->propertyId()), false)
                )
            ) {
                continue;
            }

            if (
                $filter->accountId instanceof AccountId
                && (
                    null == $dailyWaste->accountId()
                    || false === $filter->accountId->equals($dailyWaste->accountId())
                )
            ) {
                continue;
            }

            if (
                $filter->day instanceof \DateTimeImmutable
                && (
                    null == $dailyWaste->day()
                    || $dailyWaste->day()->format('Y-m-d') !== $filter->day->format('Y-m-d')
                )
            ) {
                continue;
            }

            if (
                $filter->dayFrom instanceof \DateTimeImmutable
                && (
                    null == $dailyWaste->day()
                    || $dailyWaste->day()->format('Y-m-d') <= $filter->dayFrom->format('Y-m-d')
                )
            ) {
                continue;
            }

            if (
                $filter->dayTo instanceof \DateTimeImmutable
                && (
                    null == $dailyWaste->day()
                    || $dailyWaste->day()->format('Y-m-d') >= $filter->dayTo->format('Y-m-d')
                )
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

            $results[] = $dailyWaste;
        }

        return $results;
    }

    public function totalResultsByFilter(DailyWasteFilter $filter): int
    {
        $clonedFilter = clone $filter;
        $clonedFilter->limit = 99999999999999999;
        $clonedFilter->page = 0;

        return count($this->findByFilter($filter));
    }
}
