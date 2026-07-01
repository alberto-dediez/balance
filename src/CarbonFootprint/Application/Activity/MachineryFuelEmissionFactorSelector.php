<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;

final class MachineryFuelEmissionFactorSelector
{
    public function __construct(private MachineryFuelRepository $repository) {}

    public function select(MachineryFuelId $id, int $year): EmissionFactor
    {
        $machineryFuel = $this->repository->find($id);
        if (null === $machineryFuel) {
            throw new \InvalidArgumentException(sprintf('MachineryFuel "%s" not found.', $id));
        }
        $factor = $machineryFuel->emissionFactorFor($year, EmissionFactorGroupId::default());
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for machinery fuel "%s" in year %d.', $id, $year));
        }

        return $factor;
    }
}
