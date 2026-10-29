<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OvernightStay;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/** @implements \IteratorAggregate<int, OvernightStayEmissionFactor> */
class OvernightStayEmissionFactors implements \IteratorAggregate, \Countable
{
    /** @var Collection<int, OvernightStayEmissionFactor> */
    private Collection $items;

    /** @param iterable<OvernightStayEmissionFactor> $items */
    public function __construct(iterable &$items)
    {
        if (!$items instanceof Collection) {
            $items = new ArrayCollection(
                $items instanceof \Traversable ? iterator_to_array($items) : $items,
            );
        }

        $this->items = $items;
    }

    public function add(OvernightStayEmissionFactor $emissionFactor): void
    {
        if ($this->hasForYearAndGroup($emissionFactor->year(), $emissionFactor->emissionFactorGroupId())) {
            return;
        }

        $this->items->add($emissionFactor);
    }

    public function forYearAndGroup(int $year, EmissionFactorGroupId $groupId): ?OvernightStayEmissionFactor
    {
        foreach ($this->items->toArray() as $emissionFactor) {
            if ($emissionFactor->year() === $year && $emissionFactor->emissionFactorGroupId()->equals($groupId)) {
                return $emissionFactor;
            }
        }

        return null;
    }

    public function hasForYearAndGroup(int $year, EmissionFactorGroupId $groupId): bool
    {
        return null !== $this->forYearAndGroup($year, $groupId);
    }

    public function nearestYearForGroup(int $year, EmissionFactorGroupId $groupId): ?int
    {
        $nearestYear = null;

        foreach ($this->items->toArray() as $emissionFactor) {
            if (!$emissionFactor->emissionFactorGroupId()->equals($groupId)) {
                continue;
            }

            $candidate = $emissionFactor->year();

            if (
                null === $nearestYear
                || abs($candidate - $year) < abs($nearestYear - $year)
                || (abs($candidate - $year) === abs($nearestYear - $year) && $candidate < $nearestYear)
            ) {
                $nearestYear = $candidate;
            }
        }

        return $nearestYear;
    }

    public function removeForYearAndGroup(int $year, EmissionFactorGroupId $groupId): void
    {
        foreach ($this->items->toArray() as $emissionFactor) {
            if ($emissionFactor->year() === $year && $emissionFactor->emissionFactorGroupId()->equals($groupId)) {
                $this->items->removeElement($emissionFactor);
            }
        }
    }

    /** @return int[] */
    public function years(): array
    {
        return array_map(
            static fn(OvernightStayEmissionFactor $emissionFactor): int => $emissionFactor->year(),
            $this->items->toArray(),
        );
    }

    /** @return EmissionFactorGroupId[] */
    public function groups(): array
    {
        $groups = [];

        foreach ($this->items->toArray() as $emissionFactor) {
            $groupId = $emissionFactor->emissionFactorGroupId();
            $alreadyIncluded = false;

            foreach ($groups as $existingGroupId) {
                if ($existingGroupId->equals($groupId)) {
                    $alreadyIncluded = true;
                    break;
                }
            }

            if (!$alreadyIncluded) {
                $groups[] = $groupId;
            }
        }

        return $groups;
    }

    public function count(): int
    {
        return $this->items->count();
    }

    public function getIterator(): \Traversable
    {
        return $this->items->getIterator();
    }
}
