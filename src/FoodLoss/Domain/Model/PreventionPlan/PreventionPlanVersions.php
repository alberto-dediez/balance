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

    public function lastCompletedVersion(): ?PreventionPlanVersion
    {
        $completed = array_filter(
            $this->toArray(),
            static fn(PreventionPlanVersion $version) => $version->isClosed(),
        );

        if ([] === $completed) {
            return null;
        }

        usort($completed, static fn(PreventionPlanVersion $a, PreventionPlanVersion $b) => $b->versionNumber() - $a->versionNumber());

        return $completed[0];
    }
}
