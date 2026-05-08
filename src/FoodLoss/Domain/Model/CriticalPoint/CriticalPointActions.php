<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Action\Priority;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/** @implements \IteratorAggregate<int, CriticalPointActionLink> */
final class CriticalPointActions implements \IteratorAggregate, \Countable
{
    /** @var Collection<int, CriticalPointActionLink> */
    private Collection $items;

    /** @param iterable<int, CriticalPointActionLink> $items */
    public function __construct(iterable &$items)
    {
        if (!$items instanceof Collection) {
            $items = new ArrayCollection(is_array($items) ? $items : iterator_to_array($items));
        }

        $this->items = $items;
    }

    public function add(CriticalPointActionLink $link): void
    {
        $this->items->add($link);
    }

    public function removeById(ActionId $actionId): void
    {
        foreach ($this->items->toArray() as $link) {
            if ($link->actionId()->equals($actionId)) {
                $this->items->removeElement($link);
            }
        }
    }

    /** @return ActionId[] */
    public function ids(): array
    {
        return array_map(
            fn(CriticalPointActionLink $link): ActionId => $link->actionId(),
            $this->items->toArray(),
        );
    }

    public function has(ActionId $actionId): bool
    {
        foreach ($this->items->toArray() as $link) {
            if ($link->actionId()->equals($actionId)) {
                return true;
            }
        }

        return false;
    }

    /** @return ActionId[] */
    public function byPriority(Priority $priority): array
    {
        return array_map(
            fn(CriticalPointActionLink $link): ActionId => $link->actionId(),
            array_values(array_filter(
                $this->items->toArray(),
                fn(CriticalPointActionLink $link): bool => $link->priority() === $priority,
            )),
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
