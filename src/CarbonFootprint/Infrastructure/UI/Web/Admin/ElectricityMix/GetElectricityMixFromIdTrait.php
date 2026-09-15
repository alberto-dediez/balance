<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\ElectricityMix;

use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMix;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;

trait GetElectricityMixFromIdTrait
{
    private function getElectricityMixFromId(string $electricityMixId, ElectricityMixRepository $repository): ElectricityMix
    {
        $electricityMix = $repository->find(new ElectricityMixId($electricityMixId));

        if (null == $electricityMix) {
            throw $this->createNotFoundException('Mix eléctrico no encontrado');
        }

        return $electricityMix;
    }
}
