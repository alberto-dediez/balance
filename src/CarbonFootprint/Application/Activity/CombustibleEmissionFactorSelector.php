<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;

final class CombustibleEmissionFactorSelector implements EmissionFactorSelector
{
    public function __construct(private CombustibleRepository $repository) {}

    public function activityType(): ActivityType
    {
        return ActivityType::Combustible;
    }

    public function select(string $itemId, int $year, ?ItemCondition $condition, EmissionFactorGroupId $groupId, ?string $secondaryItemId = null): EmissionFactor
    {
        $id = new CombustibleId($itemId);
        $combustible = $this->repository->find($id);
        if (null === $combustible) {
            throw new \InvalidArgumentException(sprintf('Combustible "%s" not found.', $id));
        }
        $factor = $combustible->emissionFactorFor($year, $groupId);
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for combustible "%s" in year %d.', $id, $year));
        }

        return $factor;
    }
}
