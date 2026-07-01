<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\EmissionFactorGroup;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;

trait GetEmissionFactorGroupFromIdTrait
{
    private function getEmissionFactorGroupFromId(string $emissionFactorGroupId, EmissionFactorGroupRepository $repository): EmissionFactorGroup
    {
        $emissionFactorGroup = $repository->find(new EmissionFactorGroupId($emissionFactorGroupId));

        if (null == $emissionFactorGroup) {
            throw $this->createNotFoundException('Grupo de coeficientes no encontrado');
        }

        return $emissionFactorGroup;
    }
}
