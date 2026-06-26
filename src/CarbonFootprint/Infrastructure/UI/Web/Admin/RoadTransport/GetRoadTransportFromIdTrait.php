<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\RoadTransport;

use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransport;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;

trait GetRoadTransportFromIdTrait
{
    private function getRoadTransportFromId(string $roadTransportId, RoadTransportRepository $repository): RoadTransport
    {
        $roadTransport = $repository->find(new RoadTransportId($roadTransportId));

        if (null == $roadTransport) {
            throw $this->createNotFoundException('Transporte por carretera no encontrado');
        }

        return $roadTransport;
    }
}
