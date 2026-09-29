<?php

declare(strict_types=1);

namespace App\Materiality\Infrastructure\UI\Web\Admin\MaterialitySector;

use App\Materiality\Domain\Model\MaterialitySector\MaterialitySector;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepository;

trait GetMaterialitySectorFromIdTrait
{
    private function getMaterialitySectorFromId(string $materialitySectorId, MaterialitySectorRepository $repository): MaterialitySector
    {
        $materialitySector = $repository->find(new MaterialitySectorId($materialitySectorId));

        if (null == $materialitySector) {
            throw $this->createNotFoundException('Sector de materialidad no encontrado');
        }

        return $materialitySector;
    }
}
