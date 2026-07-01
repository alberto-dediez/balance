<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;

final class MobilityEmissionFactorSelector
{
    public function __construct(private MobilityRepository $repository) {}

    public function select(MobilityId $id, int $year): EmissionFactor
    {
        $mobility = $this->repository->find($id);
        if (null === $mobility) {
            throw new \InvalidArgumentException(sprintf('Mobility "%s" not found.', $id));
        }
        $factor = $mobility->emissionFactorFor($year, EmissionFactorGroupId::default());
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for mobility "%s" in year %d.', $id, $year));
        }

        return $factor;
    }
}
