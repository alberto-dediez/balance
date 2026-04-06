<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Domain\Model\Reason\ReasonId;
use Doctrine\Common\Collections\ArrayCollection;

/** @extends ArrayCollection<int, CriticalPointReasonLink> */
final class CriticalPointReasons extends ArrayCollection
{
    public function removeById(ReasonId $reasonId): void
    {
        foreach ($this->toArray() as $link) {
            if ($link->reasonId()->equals($reasonId)) {
                $this->removeElement($link);
            }
        }
    }

    /** @return ReasonId[] */
    public function ids(): array
    {
        return array_map(
            fn(CriticalPointReasonLink $link): ReasonId => $link->reasonId(),
            $this->toArray(),
        );
    }

    public function contains(mixed $element): bool
    {
        if ($element instanceof ReasonId) {
            return $this->has($element);
        }

        return parent::contains($element);
    }

    public function has(ReasonId $reasonId): bool
    {
        foreach ($this->toArray() as $link) {
            if ($link->reasonId()->equals($reasonId)) {
                return true;
            }
        }

        return false;
    }

    /** @return ReasonId[] */
    public function byPriority(Priority $priority): array
    {
        return array_map(
            fn(CriticalPointReasonLink $link): ReasonId => $link->reasonId(),
            array_values(array_filter(
                $this->toArray(),
                fn(CriticalPointReasonLink $link): bool => $link->priority() === $priority,
            )),
        );
    }
}
