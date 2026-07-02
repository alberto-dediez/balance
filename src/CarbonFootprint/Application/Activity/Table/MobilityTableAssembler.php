<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Table;

use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\MobilityActivity;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityFilter;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;

final class MobilityTableAssembler extends AbstractActivityTableAssembler
{
    public function __construct(private MobilityRepository $mobilityRepository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Mobility;
    }

    public function loadCatalog(EventCarbonFootprint $eventCarbonFootprint): array
    {
        return $this->filterByEmissionFactor(
            $this->mobilityRepository->findByFilter(new MobilityFilter(limit: 0)),
            $eventCarbonFootprint->calculationYear(),
            $eventCarbonFootprint->emissionFactorGroupId(),
        );
    }

    protected function toRow(Activity $activity): ?ActivityTableRow
    {
        if (!$activity instanceof MobilityActivity) {
            return null;
        }

        return $this->makeRow($activity, (string) $activity->mobilityId());
    }
}
