<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\AggregateRootId;

interface CarbonFootprint
{
    public function id(): AggregateRootId;

    public function accountId(): ?AccountId;

    public function emissionFactorGroupId(): ?EmissionFactorGroupId;

    public function calculationYear(): int;

    public function isCompleted(): bool;

    /** @return ActivityId[] */
    public function activityIds(ActivityPlacement $placement): array;

    public function addActivity(ActivityId $activityId, ActivityPlacement $placement): void;

    /** @param ActivityId[] $keptIds */
    public function retainActivities(ActivityPlacement $placement, array $keptIds): void;

    public function excludeSource(CalculationStage $stage, Source $source, ExclusionReason $reason, ?string $comment = null): void;

    public function includeSource(CalculationStage $stage, Source $source): void;

    public function excludeStage(CalculationStage $stage, ExclusionReason $reason, ?string $comment = null): void;

    public function includeStage(CalculationStage $stage): void;

    public function isStageExcluded(CalculationStage $stage): bool;

    public function stageExclusionReason(CalculationStage $stage): ?ExclusionReason;

    public function stageExclusionComment(CalculationStage $stage): ?string;

    /** @return array<string, SourceExclusion> */
    public function exclusionsFor(CalculationStage $stage): array;

    /** @return list<SourceExclusion> */
    public function allExclusions(): array;

    /** @return list<ActivityReference> */
    public function activityReferences(): array;

    /** @return list<MaterialityDecision> */
    public function materialityDecisions(): array;

    public function materialityDecisionFor(Source $source): ?MaterialityDecision;

    /**
     * @param list<MaterialityDecisionInput> $decisions
     * @param array<string, string>          $omitCommentsBySource
     */
    public function applyMaterialityDecisions(array $decisions, array $omitCommentsBySource): void;

    public function clearMaterialityDecisions(): void;

    public function overrideMaterialityDecision(Source $source, UserId $userId, string $justification): void;

    public function revertMaterialityOverride(Source $source): void;

    public function hasPendingMaterialityReview(): bool;

    public function materialitySourceIsPropagable(Source $source): bool;
}
