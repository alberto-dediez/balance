<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/** @implements \IteratorAggregate<int, CriticalPointSurplusCauseLink> */
final class CriticalPointSurplusCauses implements \IteratorAggregate, \Countable
{
    /** @var Collection<int, CriticalPointSurplusCauseLink> */
    private Collection $items;

    /** @param iterable<int, CriticalPointSurplusCauseLink> $items */
    public function __construct(iterable &$items)
    {
        if (!$items instanceof Collection) {
            $items = new ArrayCollection(is_array($items) ? $items : iterator_to_array($items));
        }

        $this->items = $items;
    }

    public function add(CriticalPointSurplusCauseLink $link): void
    {
        $this->items->add($link);
    }

    public function removeById(SurplusCauseId $surplusCauseId): void
    {
        foreach ($this->items->toArray() as $link) {
            if ($link->surplusCauseId()->equals($surplusCauseId)) {
                $this->items->removeElement($link);
            }
        }
    }

    /** @return SurplusCauseId[] */
    public function ids(): array
    {
        return array_map(
            fn(CriticalPointSurplusCauseLink $link): SurplusCauseId => $link->surplusCauseId(),
            $this->items->toArray(),
        );
    }

    public function has(SurplusCauseId $surplusCauseId): bool
    {
        foreach ($this->items->toArray() as $link) {
            if ($link->surplusCauseId()->equals($surplusCauseId)) {
                return true;
            }
        }

        return false;
    }

    /** @return CriticalPointSurplusCauseLink[] */
    public function toArray(): array
    {
        return $this->items->toArray();
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
