<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Measurement;

use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;

trait GetMeasurementFromIdTrait
{
    private function getMeasurementFromId(string $containerId, MeasurementRepository $containerRepository): Measurement
    {
        $container = $containerRepository->find(new MeasurementId($containerId));

        if (null == $container) {
            throw $this->createNotFoundException('La medición no existe');
        }

        return $container;
    }
}
