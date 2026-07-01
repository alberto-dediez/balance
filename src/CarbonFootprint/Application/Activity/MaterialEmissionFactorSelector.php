<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;

final class MaterialEmissionFactorSelector
{
    public function __construct(private MaterialRepository $repository) {}

    public function select(MaterialId $id, int $year, ItemCondition $condition): EmissionFactor
    {
        $material = $this->repository->find($id);
        if (null === $material) {
            throw new \InvalidArgumentException(sprintf('Material "%s" not found.', $id));
        }
        $factor = $material->emissionFactorFor($year);
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for material "%s" in year %d.', $id, $year));
        }

        return ItemCondition::Recycled === $condition ? $factor->recycledFactor() : $factor->newFactor();
    }
}
