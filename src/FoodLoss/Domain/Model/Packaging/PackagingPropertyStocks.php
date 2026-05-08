<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Packaging;

use App\Property\Domain\Model\Property\PropertyId;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/** @implements \IteratorAggregate<int, PackagingPropertyStock> */
final class PackagingPropertyStocks implements \IteratorAggregate, \Countable
{
    /** @var Collection<int, PackagingPropertyStock> */
    private Collection $items;

    /** @param iterable<int, PackagingPropertyStock> $items */
    public function __construct(iterable &$items)
    {
        if (!$items instanceof Collection) {
            $items = new ArrayCollection(is_array($items) ? $items : iterator_to_array($items));
        }

        $this->items = $items;
    }

    public function add(PackagingPropertyStock $stock): void
    {
        $this->items->add($stock);
    }

    public function has(PropertyId $propertyId): bool
    {
        foreach ($this->items->toArray() as $stock) {
            if ($stock->propertyId()->equals($propertyId)) {
                return true;
            }
        }

        return false;
    }

    public function getByPropertyId(PropertyId $propertyId): ?PackagingPropertyStock
    {
        foreach ($this->items->toArray() as $stock) {
            if ($stock->propertyId()->equals($propertyId)) {
                return $stock;
            }
        }

        return null;
    }

    public function removeById(PropertyId $propertyId): void
    {
        foreach ($this->items->toArray() as $stock) {
            if ($stock->propertyId()->equals($propertyId)) {
                $this->items->removeElement($stock);
            }
        }
    }

    /** @return PackagingPropertyStock[] */
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
