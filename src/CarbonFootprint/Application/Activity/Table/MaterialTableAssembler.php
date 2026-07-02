<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Table;

use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\MaterialActivity;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\Material\MaterialFilter;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;

final class MaterialTableAssembler extends AbstractActivityTableAssembler
{
    public function __construct(private MaterialRepository $materialRepository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Material;
    }

    public function loadCatalog(EventCarbonFootprint $eventCarbonFootprint): array
    {
        return $this->filterByEmissionFactor(
            $this->materialRepository->findByFilter(new MaterialFilter(limit: 0)),
            $eventCarbonFootprint->calculationYear(),
            $eventCarbonFootprint->emissionFactorGroupId(),
        );
    }

    protected function toRow(Activity $activity): ?ActivityTableRow
    {
        if (!$activity instanceof MaterialActivity) {
            return null;
        }

        return $this->makeRow($activity, (string) $activity->materialId(), $activity->condition()->value);
    }
}
