<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Action\Priority;
use Doctrine\Common\Collections\ArrayCollection;

/** @extends ArrayCollection<int, CriticalPointActionLink> */
final class CriticalPointActions extends ArrayCollection
{
    public function removeById(ActionId $actionId): void
    {
        foreach ($this->toArray() as $link) {
            if ($link->actionId()->equals($actionId)) {
                $this->removeElement($link);
            }
        }
    }

    /** @return ActionId[] */
    public function ids(): array
    {
        return array_map(
            fn(CriticalPointActionLink $link) => $link->actionId(),
            $this->toArray(),
        );
    }

    public function contains(mixed $element): bool
    {
        if ($element instanceof ActionId) {
            return $this->has($element);
        }

        return parent::contains($element);
    }

    public function has(ActionId $actionId): bool
    {
        foreach ($this->toArray() as $link) {
            if ($link->actionId()->equals($actionId)) {
                return true;
            }
        }

        return false;
    }

    /** @return ActionId[] */
    public function byPriority(Priority $priority): array
    {
        return array_map(
            fn(CriticalPointActionLink $link) => $link->actionId(),
            array_values(array_filter(
                $this->toArray(),
                fn(CriticalPointActionLink $link) => $link->priority() === $priority,
            )),
        );
    }
}
