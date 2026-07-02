<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Table;

use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ElectricityActivity;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerFilter;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;

final class ElectricityTableAssembler extends AbstractActivityTableAssembler
{
    public function __construct(private ElectricityMarketerRepository $electricityMarketerRepository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Electricity;
    }

    public function loadCatalog(EventCarbonFootprint $eventCarbonFootprint): array
    {
        return $this->filterByEmissionFactor(
            $this->electricityMarketerRepository->findByFilter(new ElectricityMarketerFilter(limit: 0)),
            $eventCarbonFootprint->calculationYear(),
            $eventCarbonFootprint->emissionFactorGroupId(),
        );
    }

    protected function toRow(Activity $activity): ?ActivityTableRow
    {
        if (!$activity instanceof ElectricityActivity) {
            return null;
        }

        return $this->makeRow(
            $activity,
            (string) $activity->electricityMarketerId(),
            null,
            (string) $activity->gdOFactorId(),
        );
    }
}
