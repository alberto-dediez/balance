<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Table;

use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\OvernightStayActivity;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayFilter;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;

final class OvernightStayTableAssembler extends AbstractActivityTableAssembler
{
    public function __construct(private OvernightStayRepository $overnightStayRepository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::OvernightStay;
    }

    public function loadCatalog(EventCarbonFootprint $eventCarbonFootprint): array
    {
        return $this->filterByEmissionFactor(
            $this->overnightStayRepository->findByFilter(new OvernightStayFilter(limit: 0)),
            $eventCarbonFootprint->calculationYear(),
            $eventCarbonFootprint->emissionFactorGroupId(),
        );
    }

    protected function toRow(Activity $activity): ?ActivityTableRow
    {
        if (!$activity instanceof OvernightStayActivity) {
            return null;
        }

        return $this->makeRow($activity, (string) $activity->overnightStayId());
    }
}
