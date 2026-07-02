<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Table;

use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\MachineryFuelActivity;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelFilter;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;

final class MachineryFuelTableAssembler extends AbstractActivityTableAssembler
{
    public function __construct(private MachineryFuelRepository $machineryFuelRepository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::MachineryFuel;
    }

    public function loadCatalog(EventCarbonFootprint $eventCarbonFootprint): array
    {
        return $this->filterByEmissionFactor(
            $this->machineryFuelRepository->findByFilter(new MachineryFuelFilter(limit: 0)),
            $eventCarbonFootprint->calculationYear(),
            $eventCarbonFootprint->emissionFactorGroupId(),
        );
    }

    protected function toRow(Activity $activity): ?ActivityTableRow
    {
        if (!$activity instanceof MachineryFuelActivity) {
            return null;
        }

        return $this->makeRow($activity, (string) $activity->machineryFuelId());
    }
}
