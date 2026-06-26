<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\MachineryFuel;

use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuel;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepository;

trait GetMachineryFuelFromIdTrait
{
    private function getMachineryFuelFromId(string $machineryFuelId, MachineryFuelRepository $repository): MachineryFuel
    {
        $machineryFuel = $repository->find(new MachineryFuelId($machineryFuelId));

        if (null == $machineryFuel) {
            throw $this->createNotFoundException('Combustible de maquinaria no encontrado');
        }

        return $machineryFuel;
    }
}
