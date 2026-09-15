<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\RefrigerantGas;

use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGas;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepository;

trait GetRefrigerantGasFromIdTrait
{
    private function getRefrigerantGasFromId(string $refrigerantGasId, RefrigerantGasRepository $repository): RefrigerantGas
    {
        $refrigerantGas = $repository->find(new RefrigerantGasId($refrigerantGasId));

        if (null == $refrigerantGas) {
            throw $this->createNotFoundException('Gas refrigerante no encontrado');
        }

        return $refrigerantGas;
    }
}
