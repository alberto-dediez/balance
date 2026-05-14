<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Incidence;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/** @implements \IteratorAggregate<int, IncidenceEvidence> */
final class IncidenceEvidences implements \IteratorAggregate, \Countable
{
    /** @var Collection<int, IncidenceEvidence> */
    private Collection $items;

    /** @param iterable<int, IncidenceEvidence> $items */
    public function __construct(iterable &$items)
    {
        if (!$items instanceof Collection) {
            $items = new ArrayCollection(is_array($items) ? $items : iterator_to_array($items));
        }

        $this->items = $items;
    }

    public function add(IncidenceEvidence $evidence): void
    {
        $this->items->add($evidence);
    }

    public function removeById(IncidenceEvidenceId $evidenceId): void
    {
        foreach ($this->items->toArray() as $evidence) {
            if ($evidence->id()->equals($evidenceId)) {
                $this->items->removeElement($evidence);

                return;
            }
        }
    }

    public function findById(IncidenceEvidenceId $evidenceId): ?IncidenceEvidence
    {
        foreach ($this->items->toArray() as $evidence) {
            if ($evidence->id()->equals($evidenceId)) {
                return $evidence;
            }
        }

        return null;
    }

    public function has(IncidenceEvidenceId $evidenceId): bool
    {
        foreach ($this->items->toArray() as $evidence) {
            if ($evidence->id()->equals($evidenceId)) {
                return true;
            }
        }

        return false;
    }

    /** @return IncidenceEvidence[] */
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
