<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityQuestion;

use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/** @implements \IteratorAggregate<int, MaterialityQuestionSectorLink> */
class MaterialityQuestionSectors implements \IteratorAggregate, \Countable
{
    /** @var Collection<int, MaterialityQuestionSectorLink> */
    private Collection $items;

    /** @param iterable<int, MaterialityQuestionSectorLink> $items */
    public function __construct(iterable &$items)
    {
        if (!$items instanceof Collection) {
            $items = new ArrayCollection(is_array($items) ? $items : iterator_to_array($items));
        }

        $this->items = $items;
    }

    /** @return list<MaterialitySectorId> */
    public function ids(): array
    {
        return array_values(array_map(
            static fn(MaterialityQuestionSectorLink $link) => $link->sectorId(),
            $this->items->toArray(),
        ));
    }

    public function has(MaterialitySectorId $sectorId): bool
    {
        foreach ($this->items->toArray() as $link) {
            if ($link->sectorId()->equals($sectorId)) {
                return true;
            }
        }

        return false;
    }

    public function add(MaterialityQuestionSectorLink $link): void
    {
        $this->items->add($link);
    }

    public function removeById(MaterialitySectorId $sectorId): void
    {
        foreach ($this->items->toArray() as $link) {
            if ($link->sectorId()->equals($sectorId)) {
                $this->items->removeElement($link);
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
