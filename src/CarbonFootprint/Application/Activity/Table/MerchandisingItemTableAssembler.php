<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity\Table;

use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\MerchandisingItemActivity;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemFilter;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;

final class MerchandisingItemTableAssembler extends AbstractActivityTableAssembler
{
    public function __construct(private MerchandisingItemRepository $merchandisingItemRepository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::MerchandisingItem;
    }

    public function loadCatalog(EventCarbonFootprint $eventCarbonFootprint): array
    {
        return $this->filterByEmissionFactor(
            $this->merchandisingItemRepository->findByFilter(new MerchandisingItemFilter(limit: 0)),
            $eventCarbonFootprint->calculationYear(),
            $eventCarbonFootprint->emissionFactorGroupId(),
        );
    }

    protected function toRow(Activity $activity): ?ActivityTableRow
    {
        if (!$activity instanceof MerchandisingItemActivity) {
            return null;
        }

        return $this->makeRow($activity, (string) $activity->merchandisingItemId(), $activity->condition()->value);
    }
}
