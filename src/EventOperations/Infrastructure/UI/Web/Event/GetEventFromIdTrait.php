<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\UI\Web\Event;

use App\EventOperations\Domain\Model\Event\Event;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;

trait GetEventFromIdTrait
{
    private function getEventFromId(string $eventId, EventRepository $repository): Event
    {
        $event = $repository->find(new EventId($eventId));

        if (null === $event) {
            throw $this->createNotFoundException('Evento no encontrado');
        }

        return $event;
    }
}
