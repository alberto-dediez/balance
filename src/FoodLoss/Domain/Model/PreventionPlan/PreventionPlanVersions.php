<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use Doctrine\Common\Collections\ArrayCollection;

/** @extends ArrayCollection<int, PreventionPlanVersion> */
final class PreventionPlanVersions extends ArrayCollection
{
    public function findByVersionNumber(int $versionNumber): ?PreventionPlanVersion
    {
        foreach ($this->toArray() as $version) {
            if ($version->versionNumber() === $versionNumber) {
                return $version;
            }
        }

        return null;
    }
}
