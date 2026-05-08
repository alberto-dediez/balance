<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/** @implements \IteratorAggregate<int, PreventionPlanVersion> */
final class PreventionPlanVersions implements \IteratorAggregate, \Countable
{
    /** @var Collection<int, PreventionPlanVersion> */
    private Collection $items;

    /** @param iterable<int, PreventionPlanVersion> $items */
    public function __construct(iterable &$items)
    {
        if (!$items instanceof Collection) {
            $items = new ArrayCollection(is_array($items) ? $items : iterator_to_array($items));
        }

        $this->items = $items;
    }

    public function add(PreventionPlanVersion $version): void
    {
        $this->items->add($version);
    }

    public function findByVersionNumber(int $versionNumber): ?PreventionPlanVersion
    {
        foreach ($this->items->toArray() as $version) {
            if ($version->versionNumber() === $versionNumber) {
                return $version;
            }
        }

        return null;
    }

    public function lastCompletedVersion(): ?PreventionPlanVersion
    {
        $completed = array_filter(
            $this->items->toArray(),
            static fn(PreventionPlanVersion $version) => $version->isClosed(),
        );

        if ([] === $completed) {
            return null;
        }

        usort($completed, static fn(PreventionPlanVersion $a, PreventionPlanVersion $b) => $b->versionNumber() - $a->versionNumber());

        return $completed[0];
    }

    /** @return PreventionPlanVersion[] */
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
