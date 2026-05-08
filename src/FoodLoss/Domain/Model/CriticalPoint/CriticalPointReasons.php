<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Domain\Model\Reason\ReasonId;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/** @implements \IteratorAggregate<int, CriticalPointReasonLink> */
final class CriticalPointReasons implements \IteratorAggregate, \Countable
{
    /** @var Collection<int, CriticalPointReasonLink> */
    private Collection $items;

    /** @param iterable<int, CriticalPointReasonLink> $items */
    public function __construct(iterable &$items)
    {
        if (!$items instanceof Collection) {
            $items = new ArrayCollection(is_array($items) ? $items : iterator_to_array($items));
        }

        $this->items = $items;
    }

    public function add(CriticalPointReasonLink $link): void
    {
        $this->items->add($link);
    }

    public function removeById(ReasonId $reasonId): void
    {
        foreach ($this->items->toArray() as $link) {
            if ($link->reasonId()->equals($reasonId)) {
                $this->items->removeElement($link);
            }
        }
    }

    /** @return ReasonId[] */
    public function ids(): array
    {
        return array_map(
            fn(CriticalPointReasonLink $link): ReasonId => $link->reasonId(),
            $this->items->toArray(),
        );
    }

    public function has(ReasonId $reasonId): bool
    {
        foreach ($this->items->toArray() as $link) {
            if ($link->reasonId()->equals($reasonId)) {
                return true;
            }
        }

        return false;
    }

    /** @return ReasonId[] */
    public function byPriority(Priority $priority): array
    {
        return array_map(
            fn(CriticalPointReasonLink $link): ReasonId => $link->reasonId(),
            array_values(array_filter(
                $this->items->toArray(),
                fn(CriticalPointReasonLink $link): bool => $link->priority() === $priority,
            )),
        );
    }

    /** @return CriticalPointReasonLink[] */
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
