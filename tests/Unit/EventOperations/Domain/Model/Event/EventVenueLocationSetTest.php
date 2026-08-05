<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Domain\Model\Event;

use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventVenueLocationSet;
use App\Shared\Domain\Event\DomainEventId;
use PHPUnit\Framework\TestCase;

class EventVenueLocationSetTest extends TestCase
{
    public function testPayloadAndRebuildRoundTripWithAVenue(): void
    {
        $eventId = EventId::generate();

        $event = new EventVenueLocationSet(
            eventId: $eventId,
            venuePlaceReference: 'place-reference',
            venueLabel: 'Palacio de Congresos, Madrid',
            venueLatitude: 40.4168,
            venueLongitude: -3.7038,
        );

        $rebuilt = EventVenueLocationSet::rebuildFromPayload(DomainEventId::generate(), new \DateTimeImmutable('now'), $event->payload());

        $this->assertTrue($eventId->equals($rebuilt->eventId()));
        $this->assertSame('place-reference', $rebuilt->venuePlaceReference());
        $this->assertSame('Palacio de Congresos, Madrid', $rebuilt->venueLabel());
        $this->assertSame(40.4168, $rebuilt->venueLatitude());
        $this->assertSame(-3.7038, $rebuilt->venueLongitude());
    }

    public function testPayloadAndRebuildRoundTripWhenTheVenueIsCleared(): void
    {
        $event = new EventVenueLocationSet(
            eventId: EventId::generate(),
            venuePlaceReference: null,
            venueLabel: null,
            venueLatitude: null,
            venueLongitude: null,
        );

        $rebuilt = EventVenueLocationSet::rebuildFromPayload(DomainEventId::generate(), new \DateTimeImmutable('now'), $event->payload());

        $this->assertNull($rebuilt->venuePlaceReference());
        $this->assertNull($rebuilt->venueLabel());
        $this->assertNull($rebuilt->venueLatitude());
        $this->assertNull($rebuilt->venueLongitude());
    }

    public function testRebuildsFromAPayloadMissingTheOptionalKeys(): void
    {
        $eventId = EventId::generate();

        $rebuilt = EventVenueLocationSet::rebuildFromPayload(
            DomainEventId::generate(),
            new \DateTimeImmutable('now'),
            ['eventId' => (string) $eventId],
        );

        $this->assertTrue($eventId->equals($rebuilt->eventId()));
        $this->assertNull($rebuilt->venuePlaceReference());
        $this->assertNull($rebuilt->venueLatitude());
    }

    public function testKeepsTheIdentityAndOccurrenceDateOfTheRebuiltEvent(): void
    {
        $id = DomainEventId::generate();
        $occurredOn = new \DateTimeImmutable('2026-08-05 10:00:00');

        $event = new EventVenueLocationSet(EventId::generate(), 'place-reference', 'Madrid', 40.4168, -3.7038);

        $rebuilt = EventVenueLocationSet::rebuildFromPayload($id, $occurredOn, $event->payload());

        $this->assertTrue($id->equals($rebuilt->id()));
        $this->assertSame($occurredOn->getTimestamp(), $rebuilt->occurredOn()->getTimestamp());
    }

    public function testTheAggregateRootIdIsTheEventId(): void
    {
        $eventId = EventId::generate();

        $event = new EventVenueLocationSet($eventId, null, null, null, null);

        $this->assertSame((string) $eventId, (string) $event->aggregateRootId());
    }
}
