<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationAction;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/** @implements \IteratorAggregate<int, MitigationActionEffect> */
class MitigationActionEffects implements \IteratorAggregate, \Countable
{
    /** @var Collection<int, MitigationActionEffect> */
    private Collection $items;

    /** @param iterable<MitigationActionEffect> $items */
    public function __construct(iterable &$items)
    {
        if (!$items instanceof Collection) {
            $items = new ArrayCollection(
                $items instanceof \Traversable ? iterator_to_array($items) : $items,
            );
        }

        $this->items = $items;
    }

    public function add(MitigationActionEffect $effect): void
    {
        if ($this->hasForSourceAndItem($effect->source(), $effect->itemId())) {
            return;
        }

        $this->items->add($effect);
    }

    public function forSourceAndItem(Source $source, string $itemId): ?MitigationActionEffect
    {
        foreach ($this->items->toArray() as $effect) {
            if ($effect->source() === $source && $effect->itemId() === $itemId) {
                return $effect;
            }
        }

        return null;
    }

    public function hasForSourceAndItem(Source $source, string $itemId): bool
    {
        return null !== $this->forSourceAndItem($source, $itemId);
    }

    public function removeForSourceAndItem(Source $source, string $itemId): void
    {
        foreach ($this->items->toArray() as $effect) {
            if ($effect->source() === $source && $effect->itemId() === $itemId) {
                $this->items->removeElement($effect);
            }
        }
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
