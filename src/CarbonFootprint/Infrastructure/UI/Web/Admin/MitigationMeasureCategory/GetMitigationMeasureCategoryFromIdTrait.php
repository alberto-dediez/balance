<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\MitigationMeasureCategory;

use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategory;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryRepository;

trait GetMitigationMeasureCategoryFromIdTrait
{
    private function getMitigationMeasureCategoryFromId(string $mitigationMeasureCategoryId, MitigationMeasureCategoryRepository $repository): MitigationMeasureCategory
    {
        $mitigationMeasureCategory = $repository->find(new MitigationMeasureCategoryId($mitigationMeasureCategoryId));

        if (null == $mitigationMeasureCategory) {
            throw $this->createNotFoundException('Categoría de medidas no encontrada');
        }

        return $mitigationMeasureCategory;
    }
}
