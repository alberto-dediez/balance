<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;

final class ElectricityEmissionFactorSelector implements EmissionFactorSelector
{
    public function __construct(
        private ElectricityMarketerRepository $electricityMarketerRepository,
        private GdOFactorRepository $gdOFactorRepository,
    ) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Electricity;
    }

    public function select(string $itemId, int $year, ?ItemCondition $condition, EmissionFactorGroupId $groupId, ?string $secondaryItemId = null): EmissionFactor
    {
        if (null === $secondaryItemId || '' === $secondaryItemId) {
            throw new \InvalidArgumentException('Electricity activity requires a GdO factor id.');
        }

        $marketerId = new ElectricityMarketerId($itemId);
        $gdOFactorId = new GdOFactorId($secondaryItemId);

        $marketer = $this->electricityMarketerRepository->find($marketerId);
        if (null === $marketer) {
            throw new \InvalidArgumentException(sprintf('ElectricityMarketer "%s" not found.', $marketerId));
        }

        $gdOFactor = $this->gdOFactorRepository->find($gdOFactorId);
        if (null === $gdOFactor) {
            throw new \InvalidArgumentException(sprintf('GdOFactor "%s" not found.', $gdOFactorId));
        }

        $factor = $marketer->emissionFactorFor($year, $groupId);
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for electricity marketer "%s" in year %d.', $marketerId, $year));
        }

        return $factor;
    }
}
