<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class EventCarbonFootprintExclusions
{
    /** @var Collection<int, EventCarbonFootprintExclusion> */
    private Collection $exclusions;

    /** @param iterable<EventCarbonFootprintExclusion> $exclusions */
    public function __construct(iterable &$exclusions)
    {
        if (!$exclusions instanceof Collection) {
            $exclusions = new ArrayCollection(is_array($exclusions) ? array_values($exclusions) : iterator_to_array($exclusions));
        }
        $this->exclusions = $exclusions;
    }

    public function forPhaseAndSource(Phase $phase, Source $source): ?EventCarbonFootprintExclusion
    {
        foreach ($this->exclusions as $exclusion) {
            if ($exclusion->phase() === $phase && $exclusion->source() === $source) {
                return $exclusion;
            }
        }

        return null;
    }

    public function isExcluded(Phase $phase, Source $source): bool
    {
        return null !== $this->forPhaseAndSource($phase, $source);
    }

    public function filterByPhase(Phase $phase): self
    {
        $filtered = [];

        foreach ($this->exclusions as $exclusion) {
            if ($exclusion->phase() === $phase) {
                $filtered[] = $exclusion;
            }
        }

        return new self($filtered);
    }

    /** @return list<EventCarbonFootprintExclusion> */
    public function toArray(): array
    {
        return array_values($this->exclusions->toArray());
    }
}
