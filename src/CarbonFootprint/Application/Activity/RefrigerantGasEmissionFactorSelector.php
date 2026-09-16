<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepository;

final class RefrigerantGasEmissionFactorSelector implements EmissionFactorSelector
{
    public function __construct(private RefrigerantGasRepository $repository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::RefrigerantGas;
    }

    public function select(string $itemId, int $year, ?ItemCondition $condition, EmissionFactorGroupId $groupId, ?string $secondaryItemId = null): EmissionFactor
    {
        $id = new RefrigerantGasId($itemId);
        $refrigerantGas = $this->repository->find($id);
        if (null === $refrigerantGas) {
            throw new \InvalidArgumentException(sprintf('Refrigerant gas "%s" not found.', $id));
        }
        $factor = $refrigerantGas->emissionFactorFor($year, $groupId);
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for refrigerant gas "%s" in year %d.', $id, $year));
        }

        return $factor;
    }
}
