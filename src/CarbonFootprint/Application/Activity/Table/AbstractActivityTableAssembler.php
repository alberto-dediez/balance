<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Table;

use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItem;
use App\CarbonFootprint\Domain\Model\Combustible\Combustible;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuel;
use App\CarbonFootprint\Domain\Model\Material\Material;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItem;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStay;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransport;
use App\CarbonFootprint\Domain\Model\Waste\Waste;

abstract class AbstractActivityTableAssembler implements ActivityTableAssembler
{
    public function buildRows(EventCarbonFootprint $eventCarbonFootprint, ActivityRepository $activityRepository, ?Source $source = null): array
    {
        $activities = $eventCarbonFootprint->activities()->filterByPhase($this->phase());

        if (null !== $source) {
            $activities = $activities->filterBySource($source);
        }

        $rows = [];

        foreach ($activities->activityIds() as $activityId) {
            $activity = $activityRepository->findById($activityId);

            if (null === $activity) {
                continue;
            }

            $row = $this->toRow($activity);

            if (null === $row) {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    abstract protected function toRow(Activity $activity): ?ActivityTableRow;

    final protected function makeRow(Activity $activity, string $itemId, ?string $condition = null, ?string $itemId2 = null): ActivityTableRow
    {
        return new ActivityTableRow(
            activityId: (string) $activity->id(),
            itemId: $itemId,
            unit: $activity->quantity()->unit()->value,
            quantity: $activity->quantity()->value(),
            co2eq: $activity->emissions()->co2eq(),
            condition: $condition,
            itemId2: $itemId2,
        );
    }

    /**
     * @template T of Material|CateringItem|MerchandisingItem|Combustible|RoadTransport|MachineryFuel|ElectricityMarketer|Mobility|OvernightStay|Waste
     *
     * @param array<T> $items
     *
     * @return list<T>
     */
    final protected function filterByEmissionFactor(array $items, int $year, ?EmissionFactorGroupId $emissionFactorGroupId): array
    {
        if (null === $emissionFactorGroupId) {
            return [];
        }

        return array_values(array_filter(
            $items,
            static fn($item) => null !== $item->emissionFactorFor($year, $emissionFactorGroupId),
        ));
    }

    private function phase(): Phase
    {
        return match ($this->activityType()) {
            ActivityType::Material, ActivityType::CateringItem, ActivityType::MerchandisingItem => Phase::Materials,
            default => Phase::Assembly,
        };
    }
}
