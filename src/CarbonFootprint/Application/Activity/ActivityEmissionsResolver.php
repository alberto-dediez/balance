<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Application\Activity\Calculate\CalculateActivityEmissionsResponse;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Quantity;

final class ActivityEmissionsResolver
{
    public function __construct(
        private EmissionFactorSelectorRegistry $selectorRegistry,
        private EmissionsCalculator $calculator,
    ) {}

    public function resolve(
        CarbonFootprint $carbonFootprint,
        ActivityType $activityType,
        string $itemId,
        ?ItemCondition $condition,
        Quantity $quantity,
        ?string $secondaryItemId,
    ): CalculateActivityEmissionsResponse {
        $emissionFactorGroupId = $carbonFootprint->emissionFactorGroupId()
            ?? throw new \InvalidArgumentException(sprintf('Carbon footprint "%s" has no emission factor group configured.', $carbonFootprint->id()));

        return $this->resolveForGroupAndYear(
            $emissionFactorGroupId,
            $carbonFootprint->calculationYear(),
            $activityType,
            $itemId,
            $condition,
            $quantity,
            $secondaryItemId,
        );
    }

    public function resolveForGroupAndYear(
        EmissionFactorGroupId $emissionFactorGroupId,
        int $year,
        ActivityType $activityType,
        string $itemId,
        ?ItemCondition $condition,
        Quantity $quantity,
        ?string $secondaryItemId,
    ): CalculateActivityEmissionsResponse {
        $emissionFactor = $this->selectorRegistry
            ->forActivityType($activityType)
            ->select($itemId, $year, $condition, $emissionFactorGroupId, $secondaryItemId);

        return new CalculateActivityEmissionsResponse(
            emissionFactor: $emissionFactor,
            emissions: $this->calculator->calculate($emissionFactor, $quantity),
        );
    }
}
