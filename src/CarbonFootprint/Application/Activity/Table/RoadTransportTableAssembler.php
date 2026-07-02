<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Table;

use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\RoadTransportActivity;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportFilter;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;

final class RoadTransportTableAssembler extends AbstractActivityTableAssembler
{
    public function __construct(private RoadTransportRepository $roadTransportRepository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::RoadTransport;
    }

    public function loadCatalog(EventCarbonFootprint $eventCarbonFootprint): array
    {
        return $this->filterByEmissionFactor(
            $this->roadTransportRepository->findByFilter(new RoadTransportFilter(limit: 0)),
            $eventCarbonFootprint->calculationYear(),
            $eventCarbonFootprint->emissionFactorGroupId(),
        );
    }

    protected function toRow(Activity $activity): ?ActivityTableRow
    {
        if (!$activity instanceof RoadTransportActivity) {
            return null;
        }

        return $this->makeRow($activity, (string) $activity->roadTransportId());
    }
}
