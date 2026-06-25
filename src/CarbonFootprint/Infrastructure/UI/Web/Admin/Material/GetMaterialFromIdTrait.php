<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Material;

use App\CarbonFootprint\Domain\Model\Material\Material;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;

trait GetMaterialFromIdTrait
{
    private function getMaterialFromId(string $materialId, MaterialRepository $repository): Material
    {
        $material = $repository->find(new MaterialId($materialId));

        if (null == $material) {
            throw $this->createNotFoundException('Material no encontrado');
        }

        return $material;
    }
}
