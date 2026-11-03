<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityReference;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CalculationStage;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecision;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecisionInput;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SourceExclusion;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Security\Domain\Model\Account\AccountId;
use Doctrine\Common\Collections\Collection;

trait OrganizationalCarbonFootprintTrait
{
    private IntensityDenominators $intensityDenominators;

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

    public function intensityDenominators(): IntensityDenominators
    {
        return $this->intensityDenominators;
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

    public function isPrecalculated(): bool
    {
        return $this->precalculated;
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

    private function removeActivitiesOfStage(Scope $scope): void
    {
        foreach ($this->activityReferences() as $reference) {
            if ($reference->placement->stage->key() === $scope->key()) {
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

    public function removeEmployeeActivities(): void
    {
        foreach ($this->activityReferences() as $reference) {
            if ($reference->placement->fromEmployees) {
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

    /** @return list<MaterialityDecision> */
    public function materialityDecisions(): array
    {
        $decisions = [];

        foreach ($this->materialityDecisions as $decision) {
            $decisions[] = $decision;
        }

        return $decisions;
    }

    public function materialityDecisionFor(Source $source): ?MaterialityDecision
    {
        foreach ($this->materialityDecisions as $decision) {
            if ($decision->source() === $source) {
                return $decision;
            }
        }

        return null;
    }

    public function hasPendingMaterialityReview(): bool
    {
        return [] !== $this->pendingMaterialityReviewSources();
    }

    /** @return list<Source> */
    public function pendingMaterialityReviewSources(): array
    {
        if (!$this->status->isScope3Answered() || $this->isStageExcluded(Scope::Scope3)) {
            return [];
        }

        $sources = [];

        foreach ($this->materialityDecisions() as $decision) {
            $source = $decision->source();

            if (
                null !== $source
                && $this->materialitySourceIsPropagable($source)
                && !$decision->isOverridden()
                && $decision->verdict()->recommendsExclusion() !== $this->isSourceExcluded(Scope::Scope3, $source)
            ) {
                $sources[] = $source;
            }
        }

        return $sources;
    }

    public function materialitySourceIsPropagable(Source $source): bool
    {
        return !in_array($source, Scope::Scope3->companyOnlySources(), true);
    }

    public function hasDataOfSource(Source $source): bool
    {
        foreach ($this->activityReferences() as $reference) {
            if ($reference->placement->stage->key() === Scope::Scope3->key() && $reference->placement->source === $source) {
                return true;
            }
        }

        return false;
    }

    private function materialityDecisionOfTopic(string $topicId): ?MaterialityDecision
    {
        foreach ($this->materialityDecisions as $decision) {
            if ($decision->topicId() === $topicId) {
                return $decision;
            }
        }

        return null;
    }

    /**
     * @param list<MaterialityDecisionInput> $decisions
     *
     * @return list<Source>
     */
    private function overriddenSourcesChangingVerdict(array $decisions): array
    {
        $sources = [];

        foreach ($this->materialityDecisions() as $previous) {
            $source = $previous->source();

            if (null === $source || !$previous->isOverridden()) {
                continue;
            }

            $next = null;
            foreach ($decisions as $decision) {
                if ($decision->topicId === $previous->topicId()) {
                    $next = $decision;
                }
            }

            if (null === $next || $next->verdict !== $previous->verdict()) {
                $sources[] = $source;
            }
        }

        return $sources;
    }

    /** @return array<string, bool> */
    private function recommendedExclusionsBySource(): array
    {
        $recommended = [];

        foreach ($this->materialityDecisions() as $decision) {
            $source = $decision->source();

            if (null !== $source) {
                $recommended[$source->value] = $decision->verdict()->recommendsExclusion() && !$decision->isOverridden();
            }
        }

        return $recommended;
    }

    /**
     * @param list<MaterialityDecisionInput> $decisions
     *
     * @return list<array{topicId: string, topicNumber: int|null, scorePercent: float, thresholdPercent: int, forcedByHighMagnitude: bool, verdict: string, source: string|null}>
     */
    private function materialityDecisionEntries(array $decisions): array
    {
        $entries = [];

        foreach ($decisions as $decision) {
            $source = null === $decision->topicNumber ? null : Source::ofGhgProtocolCategory($decision->topicNumber);
            $entries[] = $decision->toPrimitives() + ['source' => $source?->value];
        }

        return $entries;
    }

    /**
     * @param array<string, bool>   $previousRecommendedExclusions
     * @param array<string, string> $omitCommentsBySource
     */
    private function propagateMaterialityDecisions(array $previousRecommendedExclusions, array $omitCommentsBySource): void
    {
        if ($this->isStageExcluded(Scope::Scope3)) {
            return;
        }

        foreach ($this->materialityDecisions() as $decision) {
            $source = $decision->source();

            if (null === $source || !$this->materialitySourceIsPropagable($source) || $decision->isOverridden()) {
                continue;
            }

            if (!$decision->verdict()->recommendsExclusion()) {
                if ($previousRecommendedExclusions[$source->value] ?? false) {
                    $this->includeSource(Scope::Scope3, $source);
                }

                continue;
            }

            if ($this->status->isScope3Answered() && ($this->isSourceExcluded(Scope::Scope3, $source) || $this->hasDataOfSource($source))) {
                continue;
            }

            $this->excludeSource(Scope::Scope3, $source, ExclusionReason::NotSignificant, $omitCommentsBySource[$source->value] ?? null);
        }
    }

    private function includeRecommendedExclusions(): void
    {
        if ($this->isStageExcluded(Scope::Scope3)) {
            return;
        }

        foreach ($this->materialityDecisions() as $decision) {
            $source = $decision->source();

            if (null !== $source && $this->materialitySourceIsPropagable($source) && $decision->verdict()->recommendsExclusion() && !$decision->isOverridden()) {
                $this->includeSource(Scope::Scope3, $source);
            }
        }
    }

    private function attachMaterialityDecision(object $child): void
    {
        if (is_array($this->materialityDecisions)) {
            $this->materialityDecisions[] = $child;
        } elseif ($this->materialityDecisions instanceof Collection) {
            $this->materialityDecisions->add($child);
        }
    }

    private function detachMaterialityDecision(string $topicId): void
    {
        foreach ($this->materialityDecisions as $key => $decision) {
            if ($decision->topicId() !== $topicId) {
                continue;
            }
            if (is_array($this->materialityDecisions)) {
                unset($this->materialityDecisions[$key]);
            } elseif ($this->materialityDecisions instanceof Collection) {
                $this->materialityDecisions->removeElement($decision);
            }

            return;
        }
    }

    private function detachAllMaterialityDecisions(): void
    {
        foreach ($this->materialityDecisions() as $decision) {
            $this->detachMaterialityDecision($decision->topicId());
        }
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
