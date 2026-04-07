<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\Property\Domain\Model\Property\PropertyId;
use Doctrine\Common\Collections\ArrayCollection;

/** @extends ArrayCollection<int, PreventionPlanPropertyLink> */
final class PreventionPlanProperties extends ArrayCollection
{
    public function removeById(PropertyId $propertyId): void
    {
        foreach ($this->toArray() as $link) {
            if ($link->propertyId()->equals($propertyId)) {
                $this->removeElement($link);
            }
        }
    }

    /** @return PropertyId[] */
    public function ids(): array
    {
        return array_map(
            fn(PreventionPlanPropertyLink $link): PropertyId => $link->propertyId(),
            $this->toArray(),
        );
    }

    public function contains(mixed $element): bool
    {
        if ($element instanceof PropertyId) {
            return $this->has($element);
        }

        return parent::contains($element);
    }

    public function has(PropertyId $propertyId): bool
    {
        foreach ($this->toArray() as $link) {
            if ($link->propertyId()->equals($propertyId)) {
                return true;
            }
        }

        return false;
    }
}
