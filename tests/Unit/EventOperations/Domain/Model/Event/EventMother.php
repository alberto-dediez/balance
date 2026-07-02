<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Domain\Model\Event;

use App\EventOperations\Domain\Model\Event\DateRange;
use App\EventOperations\Domain\Model\Event\Event;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventSector;
use App\EventOperations\Domain\Model\Event\EventSize;
use App\EventOperations\Domain\Model\Event\EventTypology;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Security\Domain\Model\Account\AccountId;

class EventMother
{
    public static function registeredEvent(): Event
    {
        return self::fromName('Event');
    }

    public static function fromName(string $name): Event
    {
        return self::from(
            name: $name,
            id: (string) EventId::generate(),
        );
    }

    public static function fromNameAndId(string $name, string $id): Event
    {
        return self::from(
            name: $name,
            id: $id,
        );
    }

    public static function fromNameAndAccountAndId(string $name, string $accountId, string $id): Event
    {
        return self::from(
            name: $name,
            id: $id,
            accountId: $accountId,
        );
    }

    public static function fromNameAndAccountAndIdAndLocation(string $name, string $accountId, string $id, string $locationId): Event
    {
        return self::from(
            name: $name,
            id: $id,
            accountId: $accountId,
            locationId: $locationId,
        );
    }

    public static function from(
        string $name,
        string $id,
        ?string $accountId = null,
        ?string $locationId = null,
    ): Event {
        return self::fromFull(
            name: $name,
            id: $id,
            accountId: $accountId,
            locationId: $locationId,
        );
    }

    public static function fromFull(
        string $name,
        string $id,
        ?string $accountId = null,
        ?string $locationId = null,
        ?EventSize $size = EventSize::Small,
        ?EventSector $sector = EventSector::Business,
        ?EventTypology $typology = EventTypology::Local,
    ): Event {
        $event = new Event(
            id: new EventId($id),
        );
        $event->register(
            name: $name,
            location: null !== $locationId ? new DestinationId($locationId) : null,
            size: $size,
            estimatedAttendees: null,
            sector: $sector,
            typology: $typology,
            assembly: new DateRange(
                startDate: new \DateTimeImmutable('2026-01-01'),
                endDate: new \DateTimeImmutable('2026-01-02'),
            ),
            celebration: new DateRange(
                startDate: new \DateTimeImmutable('2026-01-03'),
                endDate: new \DateTimeImmutable('2026-01-04'),
            ),
            breakdown: new DateRange(
                startDate: new \DateTimeImmutable('2026-01-05'),
                endDate: new \DateTimeImmutable('2026-01-06'),
            ),
            accountId: null !== $accountId ? new AccountId($accountId) : null,
        );

        return $event;
    }

    public static function random(): Event
    {
        return self::from(
            name: 'event-' . rand(100, 999),
            id: (string) EventId::generate(),
        );
    }
}
