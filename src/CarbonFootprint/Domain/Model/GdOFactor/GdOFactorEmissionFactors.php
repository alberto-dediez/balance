<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\GdOFactor;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/** @implements \IteratorAggregate<int, GdOFactorEmissionFactor> */
class GdOFactorEmissionFactors implements \IteratorAggregate, \Countable
{
    /** @var Collection<int, GdOFactorEmissionFactor> */
    private Collection $items;

    /** @param iterable<GdOFactorEmissionFactor> $items */
    public function __construct(iterable &$items)
    {
        if (!$items instanceof Collection) {
            $items = new ArrayCollection(
                $items instanceof \Traversable ? iterator_to_array($items) : $items,
            );
        }

        $this->items = $items;
    }

    public function add(GdOFactorEmissionFactor $emissionFactor): void
    {
        if ($this->hasForYear($emissionFactor->year())) {
            return;
        }

        $this->items->add($emissionFactor);
    }

    public function forYear(int $year): ?GdOFactorEmissionFactor
    {
        foreach ($this->items->toArray() as $emissionFactor) {
            if ($emissionFactor->year() === $year) {
                return $emissionFactor;
            }
        }

        return null;
    }

    public function hasForYear(int $year): bool
    {
        return null !== $this->forYear($year);
    }

    public function removeForYear(int $year): void
    {
        foreach ($this->items->toArray() as $emissionFactor) {
            if ($emissionFactor->year() === $year) {
                $this->items->removeElement($emissionFactor);
            }
        }
    }

    /** @return int[] */
    public function years(): array
    {
        return array_map(
            static fn(GdOFactorEmissionFactor $emissionFactor): int => $emissionFactor->year(),
            $this->items->toArray(),
        );
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
