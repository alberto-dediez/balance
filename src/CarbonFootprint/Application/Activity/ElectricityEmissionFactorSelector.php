<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;

final class ElectricityEmissionFactorSelector
{
    public function __construct(
        private ElectricityMarketerRepository $electricityMarketerRepository,
        private GdOFactorRepository $gdOFactorRepository,
    ) {}

    public function select(ElectricityMarketerId $marketerId, GdOFactorId $gdOFactorId, int $year): EmissionFactor
    {
        $marketer = $this->electricityMarketerRepository->find($marketerId);
        if (null === $marketer) {
            throw new \InvalidArgumentException(sprintf('ElectricityMarketer "%s" not found.', $marketerId));
        }

        $gdOFactor = $this->gdOFactorRepository->find($gdOFactorId);
        if (null === $gdOFactor) {
            throw new \InvalidArgumentException(sprintf('GdOFactor "%s" not found.', $gdOFactorId));
        }

        $factor = $marketer->emissionFactorFor($year);
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for electricity marketer "%s" in year %d.', $marketerId, $year));
        }

        return $factor;
    }
}
