<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/** @implements \IteratorAggregate<int, CriticalPointServiceCategoryLink> */
final class CriticalPointServiceCategories implements \IteratorAggregate, \Countable
{
    /** @var Collection<int, CriticalPointServiceCategoryLink> */
    private Collection $items;

    /** @param iterable<int, CriticalPointServiceCategoryLink> $items */
    public function __construct(iterable &$items)
    {
        if (!$items instanceof Collection) {
            $items = new ArrayCollection(is_array($items) ? $items : iterator_to_array($items));
        }

        $this->items = $items;
    }

    public function add(CriticalPointServiceCategoryLink $link): void
    {
        $this->items->add($link);
    }

    public function removeById(ServiceCategoryId $serviceCategoryId): void
    {
        foreach ($this->items->toArray() as $link) {
            if ($link->serviceCategoryId()->equals($serviceCategoryId)) {
                $this->items->removeElement($link);
            }
        }
    }

    /** @return ServiceCategoryId[] */
    public function ids(): array
    {
        return array_map(
            fn(CriticalPointServiceCategoryLink $link): ServiceCategoryId => $link->serviceCategoryId(),
            $this->items->toArray(),
        );
    }

    public function has(ServiceCategoryId $serviceCategoryId): bool
    {
        foreach ($this->items->toArray() as $link) {
            if ($link->serviceCategoryId()->equals($serviceCategoryId)) {
                return true;
            }
        }

        return false;
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
