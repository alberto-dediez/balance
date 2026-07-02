<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Table;

use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\WasteActivity;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\Waste\WasteFilter;
use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;

final class WasteTableAssembler extends AbstractActivityTableAssembler
{
    public function __construct(private WasteRepository $wasteRepository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Waste;
    }

    public function loadCatalog(EventCarbonFootprint $eventCarbonFootprint): array
    {
        return $this->filterByEmissionFactor(
            $this->wasteRepository->findByFilter(new WasteFilter(limit: 0)),
            $eventCarbonFootprint->calculationYear(),
            $eventCarbonFootprint->emissionFactorGroupId(),
        );
    }

    protected function toRow(Activity $activity): ?ActivityTableRow
    {
        if (!$activity instanceof WasteActivity) {
            return null;
        }

        return $this->makeRow($activity, (string) $activity->wasteId());
    }
}
