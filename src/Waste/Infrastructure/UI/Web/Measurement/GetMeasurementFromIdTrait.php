<?php

namespace App\Waste\Infrastructure\UI\Web\Measurement;

use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use Symfony\Component\Translation\TranslatableMessage;
use App\Waste\Domain\Model\Measurement\MeasurementRepository;

trait GetMeasurementFromIdTrait
{
    private function getMeasurementFromId(string $containerId, MeasurementRepository $containerRepository): Measurement
    {
        $container = $containerRepository->find(new MeasurementId($containerId));

        if (null == $container) {
            throw $this->createNotFoundException(new TranslatableMessage("La medición no existe"));
        }

        return $container;
    }
}