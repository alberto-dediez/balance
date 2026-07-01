<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;

final class CateringItemEmissionFactorSelector
{
    public function __construct(private CateringItemRepository $repository) {}

    public function select(CateringItemId $id, int $year, ItemCondition $condition): EmissionFactor
    {
        $cateringItem = $this->repository->find($id);
        if (null === $cateringItem) {
            throw new \InvalidArgumentException(sprintf('CateringItem "%s" not found.', $id));
        }
        $factor = $cateringItem->emissionFactorFor($year);
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for catering item "%s" in year %d.', $id, $year));
        }

        return ItemCondition::Recycled === $condition ? $factor->recycledFactor() : $factor->newFactor();
    }
}
