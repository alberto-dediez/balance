<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/** @implements \IteratorAggregate<int, PreventionPlanSection> */
final class PreventionPlanSections implements \IteratorAggregate, \Countable
{
    /** @var Collection<int, PreventionPlanSection> $items */
    private Collection $items;

    /** @param iterable<int, PreventionPlanSection> $items */
    public function __construct(iterable &$items)
    {
        $items = !$items instanceof Collection ? new ArrayCollection(array_values(iterator_to_array($items))) : $items;

        $this->items = $items;
    }

    public function add(PreventionPlanSection $section): void
    {
        $this->items->add($section);
    }

    public function findById(PreventionPlanSectionId $sectionId): ?PreventionPlanSection
    {
        foreach ($this->items as $section) {
            if ($section->id()->equals($sectionId)) {
                return $section;
            }
        }

        return null;
    }

    public function removeById(PreventionPlanSectionId $sectionId): void
    {
        foreach ($this->items->toArray() as $section) {
            if ($section->id()->equals($sectionId)) {
                $this->items->removeElement($section);

                return;
            }
        }
    }

    public function clear(): void
    {
        $this->items->clear();
    }

    /** @return PreventionPlanSection[] */
    public function toArray(): array
    {
        return array_values($this->items->toArray());
    }

    public function count(): int
    {
        return $this->items->count();
    }

    /** @return \Traversable<int, PreventionPlanSection> */
    public function getIterator(): \Traversable
    {
        return $this->items->getIterator();
    }
}
