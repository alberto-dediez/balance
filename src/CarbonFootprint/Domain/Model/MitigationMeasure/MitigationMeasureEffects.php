<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationMeasure;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/** @implements \IteratorAggregate<int, MitigationMeasureEffect> */
class MitigationMeasureEffects implements \IteratorAggregate, \Countable
{
    /** @var Collection<int, MitigationMeasureEffect> */
    private Collection $items;

    /** @param iterable<MitigationMeasureEffect> $items */
    public function __construct(iterable &$items)
    {
        if (!$items instanceof Collection) {
            $items = new ArrayCollection(
                $items instanceof \Traversable ? iterator_to_array($items) : $items,
            );
        }

        $this->items = $items;
    }

    public function add(MitigationMeasureEffect $effect): void
    {
        if ($this->hasForSource($effect->source())) {
            return;
        }

        $this->items->add($effect);
    }

    public function forSource(Source $source): ?MitigationMeasureEffect
    {
        foreach ($this->items->toArray() as $effect) {
            if ($effect->source() === $source) {
                return $effect;
            }
        }

        return null;
    }

    public function hasForSource(Source $source): bool
    {
        return null !== $this->forSource($source);
    }

    public function removeForSource(Source $source): void
    {
        foreach ($this->items->toArray() as $effect) {
            if ($effect->source() === $source) {
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
