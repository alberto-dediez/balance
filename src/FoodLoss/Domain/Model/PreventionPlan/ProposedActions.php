<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\Property\Domain\Model\Property\PropertyId;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/** @implements \IteratorAggregate<int, ProposedAction> */
final class ProposedActions implements \IteratorAggregate, \Countable
{
    /** @var Collection<int, ProposedAction> */
    private Collection $items;

    /** @param iterable<int, ProposedAction> $items */
    public function __construct(iterable &$items)
    {
        $items = !$items instanceof Collection ? new ArrayCollection(array_values(iterator_to_array($items))) : $items;

        $this->items = $items;
    }

    public function add(ProposedAction $proposedAction): void
    {
        $this->items->add($proposedAction);
    }

    public function has(PropertyId $propertyId, ActionId $actionId, CriticalPointId $criticalPointId): bool
    {
        foreach ($this->items->toArray() as $proposedAction) {
            if (
                !$proposedAction->propertyId()->equals($propertyId)
                || !$proposedAction->actionId()->equals($actionId)
                || !$proposedAction->criticalPointId()->equals($criticalPointId)
            ) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function removeByIds(PropertyId $propertyId, ActionId $actionId, CriticalPointId $criticalPointId): void
    {
        foreach ($this->items->toArray() as $proposedAction) {
            if (
                !$proposedAction->propertyId()->equals($propertyId)
                || !$proposedAction->actionId()->equals($actionId)
                || !$proposedAction->criticalPointId()->equals($criticalPointId)
            ) {
                continue;
            }

            $this->items->removeElement($proposedAction);
        }
    }

    public function removeByCriticalPointId(CriticalPointId $criticalPointId): void
    {
        foreach ($this->items->toArray() as $proposedAction) {
            if ($proposedAction->criticalPointId()->equals($criticalPointId)) {
                $this->items->removeElement($proposedAction);
            }
        }
    }

    /** @return ProposedAction[] */
    public function toArray(): array
    {
        return array_values($this->items->toArray());
    }

    public function count(): int
    {
        return $this->items->count();
    }

    /** @return \Traversable<int, ProposedAction> */
    public function getIterator(): \Traversable
    {
        return $this->items->getIterator();
    }
}
