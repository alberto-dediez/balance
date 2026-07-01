<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;

final class CombustibleEmissionFactorSelector
{
    public function __construct(private CombustibleRepository $repository) {}

    public function select(CombustibleId $id, int $year): EmissionFactor
    {
        $combustible = $this->repository->find($id);
        if (null === $combustible) {
            throw new \InvalidArgumentException(sprintf('Combustible "%s" not found.', $id));
        }
        $factor = $combustible->emissionFactorFor($year);
        if (null === $factor) {
            throw new \InvalidArgumentException(sprintf('No emission factor for combustible "%s" in year %d.', $id, $year));
        }

        return $factor;
    }
}
