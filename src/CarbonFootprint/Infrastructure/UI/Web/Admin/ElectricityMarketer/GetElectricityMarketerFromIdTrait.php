<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\ElectricityMarketer;

use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepository;

trait GetElectricityMarketerFromIdTrait
{
    private function getElectricityMarketerFromId(string $electricityMarketerId, ElectricityMarketerRepository $repository): ElectricityMarketer
    {
        $electricityMarketer = $repository->find(new ElectricityMarketerId($electricityMarketerId));

        if (null == $electricityMarketer) {
            throw $this->createNotFoundException('Comercializadora eléctrica no encontrada');
        }

        return $electricityMarketer;
    }
}
