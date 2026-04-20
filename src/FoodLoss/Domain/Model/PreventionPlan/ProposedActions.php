<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\Property\Domain\Model\Property\PropertyId;
use Doctrine\Common\Collections\ArrayCollection;

/** @extends ArrayCollection<int, ProposedAction> */
final class ProposedActions extends ArrayCollection
{
    public function has(PropertyId $propertyId, ActionId $actionId, CriticalPointId $criticalPointId): bool
    {
        foreach ($this->toArray() as $proposedAction) {
            if (
                !$proposedAction->propertyId()->equals($propertyId)
                || !$proposedAction->actionId()->equals($actionId)
                || !$proposedAction->criticalPointId()->equals($criticalPointId)
            ) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function removeByIds(PropertyId $propertyId, ActionId $actionId, CriticalPointId $criticalPointId): void
    {
        foreach ($this->toArray() as $proposedAction) {
            if (
                !$proposedAction->propertyId()->equals($propertyId)
                || !$proposedAction->actionId()->equals($actionId)
                || !$proposedAction->criticalPointId()->equals($criticalPointId)
            ) {
                continue;
            }

            $this->removeElement($proposedAction);
        }
    }

    public function removeByCriticalPointId(CriticalPointId $criticalPointId): void
    {
        foreach ($this->toArray() as $proposedAction) {
            if ($proposedAction->criticalPointId()->equals($criticalPointId)) {
                $this->removeElement($proposedAction);
            }
        }
    }
}
