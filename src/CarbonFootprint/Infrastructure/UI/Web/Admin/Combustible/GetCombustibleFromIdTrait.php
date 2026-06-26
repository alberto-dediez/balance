<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Combustible;

use App\CarbonFootprint\Domain\Model\Combustible\Combustible;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;

trait GetCombustibleFromIdTrait
{
    private function getCombustibleFromId(string $combustibleId, CombustibleRepository $repository): Combustible
    {
        $combustible = $repository->find(new CombustibleId($combustibleId));

        if (null == $combustible) {
            throw $this->createNotFoundException('Combustible no encontrado');
        }

        return $combustible;
    }
}
