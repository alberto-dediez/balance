<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;

final class CapitalGoodEmissionFactorSelector implements EmissionFactorSelector
{
    public function __construct(private CapitalGoodRepository $repository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::CapitalGood;
    }

    public function select(string $itemId, int $year, ?ItemCondition $condition, EmissionFactorGroupId $groupId, ?string $secondaryItemId = null): EmissionFactor
    {
        $id = new CapitalGoodId($itemId);
        $capitalGood = $this->repository->find($id);
        if (null === $capitalGood) {
            throw new \InvalidArgumentException(sprintf('CapitalGood "%s" not found.', $id));
        }
        $factor = $capitalGood->emissionFactorFor($year, $groupId);
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for capital good "%s" in year %d.', $id, $year));
        }

        return $factor->emissionFactor();
    }
}
