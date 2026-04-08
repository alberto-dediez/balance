<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\FoodLoss\Domain\Model\Action\ActionId;
use App\Property\Domain\Model\Property\PropertyId;
use Doctrine\Common\Collections\ArrayCollection;

/** @extends ArrayCollection<int, ProposedAction> */
final class ProposedActions extends ArrayCollection
{
    public function has(PropertyId $propertyId, ActionId $actionId): bool
    {
        foreach ($this->toArray() as $proposedAction) {
            if ($proposedAction->propertyId()->equals($propertyId) && $proposedAction->actionId()->equals($actionId)) {
                return true;
            }
        }

        return false;
    }

    public function removeByIds(PropertyId $propertyId, ActionId $actionId): void
    {
        foreach ($this->toArray() as $proposedAction) {
            if ($proposedAction->propertyId()->equals($propertyId) && $proposedAction->actionId()->equals($actionId)) {
                $this->removeElement($proposedAction);
            }
        }
    }
}
