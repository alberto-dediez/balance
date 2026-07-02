<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Table;

use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivity;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleFilter;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;

final class CombustibleTableAssembler extends AbstractActivityTableAssembler
{
    public function __construct(private CombustibleRepository $combustibleRepository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Combustible;
    }

    public function loadCatalog(EventCarbonFootprint $eventCarbonFootprint): array
    {
        return $this->filterByEmissionFactor(
            $this->combustibleRepository->findByFilter(new CombustibleFilter(limit: 0)),
            $eventCarbonFootprint->calculationYear(),
            $eventCarbonFootprint->emissionFactorGroupId(),
        );
    }

    protected function toRow(Activity $activity): ?ActivityTableRow
    {
        if (!$activity instanceof CombustibleActivity) {
            return null;
        }

        return $this->makeRow($activity, (string) $activity->combustibleId());
    }
}
