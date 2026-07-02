<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Table;

use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\CateringItemActivity;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemFilter;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;

final class CateringItemTableAssembler extends AbstractActivityTableAssembler
{
    public function __construct(private CateringItemRepository $cateringItemRepository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::CateringItem;
    }

    public function loadCatalog(EventCarbonFootprint $eventCarbonFootprint): array
    {
        return $this->filterByEmissionFactor(
            $this->cateringItemRepository->findByFilter(new CateringItemFilter(limit: 0)),
            $eventCarbonFootprint->calculationYear(),
            $eventCarbonFootprint->emissionFactorGroupId(),
        );
    }

    protected function toRow(Activity $activity): ?ActivityTableRow
    {
        if (!$activity instanceof CateringItemActivity) {
            return null;
        }

        return $this->makeRow($activity, (string) $activity->cateringItemId(), $activity->condition()->value);
    }
}
