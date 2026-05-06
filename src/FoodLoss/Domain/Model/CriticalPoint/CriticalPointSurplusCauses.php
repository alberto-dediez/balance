<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseId;
use Doctrine\Common\Collections\ArrayCollection;

/** @extends ArrayCollection<int, CriticalPointSurplusCauseLink> */
final class CriticalPointSurplusCauses extends ArrayCollection
{
    public function removeById(SurplusCauseId $surplusCauseId): void
    {
        foreach ($this->toArray() as $link) {
            if ($link->surplusCauseId()->equals($surplusCauseId)) {
                $this->removeElement($link);
            }
        }
    }

    /** @return SurplusCauseId[] */
    public function ids(): array
    {
        return array_map(
            fn(CriticalPointSurplusCauseLink $link): SurplusCauseId => $link->surplusCauseId(),
            $this->toArray(),
        );
    }

    public function contains(mixed $element): bool
    {
        if ($element instanceof SurplusCauseId) {
            return $this->has($element);
        }

        return parent::contains($element);
    }

    public function has(SurplusCauseId $surplusCauseId): bool
    {
        foreach ($this->toArray() as $link) {
            if ($link->surplusCauseId()->equals($surplusCauseId)) {
                return true;
            }
        }

        return false;
    }
}
