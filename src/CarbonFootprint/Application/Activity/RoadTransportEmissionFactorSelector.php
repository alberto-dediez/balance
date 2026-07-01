<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;

final class RoadTransportEmissionFactorSelector
{
    public function __construct(private RoadTransportRepository $repository) {}

    public function select(RoadTransportId $id, int $year): EmissionFactor
    {
        $roadTransport = $this->repository->find($id);
        if (null === $roadTransport) {
            throw new \InvalidArgumentException(sprintf('RoadTransport "%s" not found.', $id));
        }
        $factor = $roadTransport->emissionFactorFor($year, EmissionFactorGroupId::default());
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for road transport "%s" in year %d.', $id, $year));
        }

        return $factor;
    }
}
