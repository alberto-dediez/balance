<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\Property\Domain\Model\Property\PropertyId;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/** @implements \IteratorAggregate<int, PreventionPlanVersionPropertyLink> */
final class PreventionPlanVersionProperties implements \IteratorAggregate, \Countable
{
    /** @var Collection<int, PreventionPlanVersionPropertyLink> */
    private Collection $items;

    /** @param iterable<int, PreventionPlanVersionPropertyLink> $items */
    public function __construct(iterable &$items)
    {
        $items = !$items instanceof Collection ? new ArrayCollection(array_values(iterator_to_array($items))) : $items;

        $this->items = $items;
    }

    public function add(PreventionPlanVersionPropertyLink $link): void
    {
        $this->items->add($link);
    }

    public function removeById(PropertyId $propertyId): void
    {
        foreach ($this->items->toArray() as $link) {
            if ($link->propertyId()->equals($propertyId)) {
                $this->items->removeElement($link);
            }
        }
    }

    /** @return PropertyId[] */
    public function ids(): array
    {
        return array_map(
            fn(PreventionPlanVersionPropertyLink $link): PropertyId => $link->propertyId(),
            $this->items->toArray(),
        );
    }

    public function has(PropertyId $propertyId): bool
    {
        foreach ($this->items->toArray() as $link) {
            if ($link->propertyId()->equals($propertyId)) {
                return true;
            }
        }

        return false;
    }

    public function count(): int
    {
        return $this->items->count();
    }

    /** @return \Traversable<int, PreventionPlanVersionPropertyLink> */
    public function getIterator(): \Traversable
    {
        return $this->items->getIterator();
    }
}
