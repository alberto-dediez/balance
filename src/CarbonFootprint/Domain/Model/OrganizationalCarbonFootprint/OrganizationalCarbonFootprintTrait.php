<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityReference;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CalculationStage;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SourceExclusion;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\Common\Collections\Collection;

trait OrganizationalCarbonFootprintTrait
{
    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function emissionFactorGroupId(): ?EmissionFactorGroupId
    {
        return $this->emissionFactorGroupId;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function calculationYear(): int
    {
        return $this->year;
    }

    public function totalEmissions(): Emissions
    {
        return $this->totalEmissions;
    }

    public function status(): OrganizationalCarbonFootprintStatus
    {
        return $this->status;
    }

    public function isCompleted(): bool
    {
        return $this->status->isCompleted();
    }

    public function isMitigationPlanStarted(): bool
    {
        return $this->mitigationPlanStarted;
    }

    public function scope3Excluded(): bool
    {
        return $this->scope3Excluded;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    /** @return ActivityId[] */
    public function activityIds(ActivityPlacement $placement): array
    {
        $activityIds = [];

        foreach ($this->activities as $activity) {
            if ($activity->placement()->equals($placement)) {
                $activityIds[] = $activity->activityId();
            }
        }

        return $activityIds;
    }

    /** @param ActivityId[] $keptIds */
    public function retainActivities(ActivityPlacement $placement, array $keptIds): void
    {
        foreach ($this->activityIds($placement) as $existingId) {
            $kept = false;
            foreach ($keptIds as $keptId) {
                if ($keptId->equals($existingId)) {
                    $kept = true;

                    break;
                }
            }

            if (!$kept) {
                $this->removeActivity($existingId);
            }
        }
    }

    private function removeActivitiesOfSource(Scope $scope, Source $source): void
    {
        foreach ($this->activityReferences() as $reference) {
            if ($reference->placement->stage->key() === $scope->key() && $reference->placement->source === $source) {
                $this->removeActivity($reference->activityId);
            }
        }
    }

    public function removeActivitiesOfProvider(ProviderId $providerId): void
    {
        foreach ($this->activityReferences() as $reference) {
            if (null !== $reference->placement->providerId && $reference->placement->providerId->equals($providerId)) {
                $this->removeActivity($reference->activityId);
            }
        }
    }

    /** @return list<ActivityReference> */
    public function activityReferences(): array
    {
        $references = [];

        foreach ($this->activities as $activity) {
            $references[] = new ActivityReference($activity->activityId(), $activity->placement());
        }

        return $references;
    }

    /** @return array<string, SourceExclusion> */
    public function exclusionsFor(CalculationStage $stage): array
    {
        $indexed = [];

        foreach ($this->exclusions as $exclusion) {
            if ($exclusion->stage()->key() === $stage->key()) {
                $indexed[$exclusion->source()->value] = $exclusion;
            }
        }

        return $indexed;
    }

    /** @return list<SourceExclusion> */
    public function allExclusions(): array
    {
        $all = [];

        foreach ($this->exclusions as $exclusion) {
            $all[] = $exclusion;
        }

        return $all;
    }

    public function exclusionFor(CalculationStage $stage, Source $source): ?SourceExclusion
    {
        foreach ($this->exclusions as $exclusion) {
            if ($exclusion->stage()->key() === $stage->key() && $exclusion->source() === $source) {
                return $exclusion;
            }
        }

        return null;
    }

    public function isSourceExcluded(CalculationStage $stage, Source $source): bool
    {
        return null !== $this->exclusionFor($stage, $source);
    }

    public function isStageExcluded(CalculationStage $stage): bool
    {
        return $stage->key() === Scope::Scope3->key() && $this->scope3Excluded;
    }

    public function stageExclusionReason(CalculationStage $stage): ?ExclusionReason
    {
        return $stage->key() === Scope::Scope3->key() ? $this->scope3ExclusionReason : null;
    }

    public function stageExclusionComment(CalculationStage $stage): ?string
    {
        return $stage->key() === Scope::Scope3->key() ? $this->scope3ExclusionComment : null;
    }

    private function attachActivity(object $child): void
    {
        if (is_array($this->activities)) {
            $this->activities[] = $child;
        } elseif ($this->activities instanceof Collection) {
            $this->activities->add($child);
        }
    }

    private function detachActivity(ActivityId $activityId): void
    {
        foreach ($this->activities as $key => $activity) {
            if (!$activity->activityId()->equals($activityId)) {
                continue;
            }
            if (is_array($this->activities)) {
                unset($this->activities[$key]);
            } elseif ($this->activities instanceof Collection) {
                $this->activities->removeElement($activity);
            }

            return;
        }
    }

    private function attachExclusion(object $child): void
    {
        if (is_array($this->exclusions)) {
            $this->exclusions[] = $child;
        } elseif ($this->exclusions instanceof Collection) {
            $this->exclusions->add($child);
        }
    }

    private function detachExclusion(CalculationStage $stage, Source $source): void
    {
        foreach ($this->exclusions as $key => $exclusion) {
            if ($exclusion->stage()->key() !== $stage->key() || $exclusion->source() !== $source) {
                continue;
            }
            if (is_array($this->exclusions)) {
                unset($this->exclusions[$key]);
            } elseif ($this->exclusions instanceof Collection) {
                $this->exclusions->removeElement($exclusion);
            }

            return;
        }
    }

    private function ensureIsEditable(): void
    {
        if ($this->status->isCompleted()) {
            throw new OrganizationalCarbonFootprintAlreadyCompletedException('Organizational carbon footprint is completed and can no longer be edited');
        }
    }
}
