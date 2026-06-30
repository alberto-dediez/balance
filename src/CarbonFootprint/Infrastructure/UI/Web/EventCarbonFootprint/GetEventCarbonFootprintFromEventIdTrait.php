<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Event\EventId;

trait GetEventCarbonFootprintFromEventIdTrait
{
    private function getEventCarbonFootprintFromEventId(string $eventId, EventCarbonFootprintRepository $repository): EventCarbonFootprint
    {
        $eventCarbonFootprint = $repository->findByEventId(new EventId($eventId));

        if (null === $eventCarbonFootprint) {
            throw $this->createNotFoundException('Cálculo de Huella de Carbono no encontrado');
        }

        return $eventCarbonFootprint;
    }
}
