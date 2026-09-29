<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\MitigationMeasure;

use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasure;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureId;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureRepository;

trait GetMitigationMeasureFromIdTrait
{
    private function getMitigationMeasureFromId(string $mitigationMeasureId, MitigationMeasureRepository $repository): MitigationMeasure
    {
        $mitigationMeasure = $repository->find(new MitigationMeasureId($mitigationMeasureId));

        if (null == $mitigationMeasure) {
            throw $this->createNotFoundException('Medida no encontrada');
        }

        return $mitigationMeasure;
    }
}
