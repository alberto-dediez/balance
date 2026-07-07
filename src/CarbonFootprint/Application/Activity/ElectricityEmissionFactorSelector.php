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
        $marketerId = '' !== $itemId ? new ElectricityMarketerId($itemId) : null;
        $gdOFactorId = null !== $secondaryItemId && '' !== $secondaryItemId ? new GdOFactorId($secondaryItemId) : null;

        if (null === $marketerId && null === $gdOFactorId) {
            throw new \InvalidArgumentException('Electricity activity requires an electricity marketer or a GdO factor.');
        }

        $marketer = null;
        if (null !== $marketerId) {
            $marketer = $this->electricityMarketerRepository->find($marketerId);
            if (null === $marketer) {
                throw new \InvalidArgumentException(sprintf('ElectricityMarketer "%s" not found.', $marketerId));
            }
        }

        if (null !== $gdOFactorId) {
            $gdOFactor = $this->gdOFactorRepository->find($gdOFactorId);
            if (null === $gdOFactor) {
                throw new \InvalidArgumentException(sprintf('GdOFactor "%s" not found.', $gdOFactorId));
            }

            $factor = $gdOFactor->emissionFactorFor($year, $groupId);
            if (null === $factor) {
                throw new \InvalidArgumentException(sprintf('No emission factor for GdO factor "%s" in year %d.', $gdOFactorId, $year));
            }

            return $factor;
        }

        $factor = $marketer->emissionFactorFor($year, $groupId);
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for electricity marketer "%s" in year %d.', $marketerId, $year));
        }

        return $factor;
    }
}
