<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\AlertConfiguration;

use App\Waste\Domain\Model\Alert\AlertType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/** @implements \IteratorAggregate<int, AlertConfigurationItem> */
class AlertConfigurationItems implements \IteratorAggregate, \Countable
{
    /** @var Collection<int, AlertConfigurationItem> */
    private Collection $items;

    /** @param iterable<int, AlertConfigurationItem> $items */
    public function __construct(iterable &$items)
    {
        if (!$items instanceof Collection) {
            $items = new ArrayCollection(is_array($items) ? $items : iterator_to_array($items));
        }
        $this->items = $items;
    }

    public function getByType(AlertType $type): ?AlertConfigurationItem
    {
        foreach ($this->items->toArray() as $item) {
            if ($item->type() === $type) {
                return $item;
            }
        }

        return null;
    }

    /** @return AlertConfigurationItem[] */
    public function getAllByType(AlertType $type): array
    {
        return array_values(array_filter(
            $this->items->toArray(),
            fn(AlertConfigurationItem $item) => $item->type() === $type,
        ));
    }

    /** @return AlertConfigurationItem[] */
    public function enabledItems(): array
    {
        return array_values(array_filter(
            $this->items->toArray(),
            fn(AlertConfigurationItem $item) => $item->enabled(),
        ));
    }

    public function addItem(AlertConfigurationItem $item): void
    {
        $this->items->add($item);
    }

    public function removeItem(AlertConfigurationItem $item): void
    {
        $this->items->removeElement($item);
    }

    public function has(AlertType $type): bool
    {
        return null !== $this->getByType($type);
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
