<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRootId;

interface CarbonFootprint
{
    public function id(): AggregateRootId;

    public function accountId(): ?AccountId;

    public function emissionFactorGroupId(): ?EmissionFactorGroupId;

    public function calculationYear(): int;

    /** @return ActivityId[] */
    public function activityIds(ActivityPlacement $placement): array;

    public function addActivity(ActivityId $activityId, ActivityPlacement $placement): void;

    /** @param ActivityId[] $keptIds */
    public function retainActivities(ActivityPlacement $placement, array $keptIds): void;

    public function excludeSource(CalculationStage $stage, Source $source, ExclusionReason $reason, ?string $comment = null): void;

    public function includeSource(CalculationStage $stage, Source $source): void;

    /** @return array<string, SourceExclusion> */
    public function exclusionsFor(CalculationStage $stage): array;

    /** @return list<SourceExclusion> */
    public function allExclusions(): array;

    /** @return list<ActivityReference> */
    public function activityReferences(): array;
}
