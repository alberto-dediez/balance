<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintStatus;
use App\EventOperations\Domain\Model\Event\DateRange;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Domain\Model\Account\AccountId;

class EventCarbonFootprintMother
{
    public static function registeredEventCarbonFootprint(): EventCarbonFootprint
    {
        return self::fromEventId((string) EventId::generate());
    }

    public static function fromEventId(string $eventId): EventCarbonFootprint
    {
        return self::from(
            eventId: $eventId,
            id: (string) EventCarbonFootprintId::generate(),
        );
    }

    public static function fromEventIdAndId(string $eventId, string $id): EventCarbonFootprint
    {
        return self::from(
            eventId: $eventId,
            id: $id,
        );
    }

    public static function fromEventIdAndAccount(string $eventId, string $accountId): EventCarbonFootprint
    {
        return self::from(
            eventId: $eventId,
            id: (string) EventCarbonFootprintId::generate(),
            accountId: $accountId,
        );
    }

    public static function from(
        string $eventId,
        string $id,
        ?string $accountId = null,
    ): EventCarbonFootprint {
        $eventCarbonFootprint = new EventCarbonFootprint(
            id: new EventCarbonFootprintId($id),
        );
        $eventCarbonFootprint->register(
            eventId: new EventId($eventId),
            accountId: new AccountId($accountId ?? 'account-id'),
            calculationPeriod: new DateRange(
                startDate: new \DateTimeImmutable('2026-01-01'),
                endDate: new \DateTimeImmutable('2026-01-31'),
            ),
            totalEmissions: new Emissions(),
            emissionsPerAttendee: new Emissions(),
            status: EventCarbonFootprintStatus::Started,
        );

        return $eventCarbonFootprint;
    }

    public static function random(): EventCarbonFootprint
    {
        return self::from(
            eventId: (string) EventId::generate(),
            id: (string) EventCarbonFootprintId::generate(),
        );
    }
}
