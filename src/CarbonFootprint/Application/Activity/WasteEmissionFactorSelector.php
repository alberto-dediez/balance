<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;

final class WasteEmissionFactorSelector
{
    public function __construct(private WasteRepository $repository) {}

    public function select(WasteId $id, int $year): EmissionFactor
    {
        $waste = $this->repository->find($id);
        if (null === $waste) {
            throw new \InvalidArgumentException(sprintf('Waste "%s" not found.', $id));
        }
        $factor = $waste->emissionFactorFor($year);
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for waste "%s" in year %d.', $id, $year));
        }

        return $factor;
    }
}
