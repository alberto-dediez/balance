<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Application\Event;

use App\EventOperations\Application\Event\EventVenueLocationSetter;
use App\EventOperations\Domain\Model\Event\Event;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\Shared\Domain\Model\Geo\InMemoryPlaceFinder;
use PHPUnit\Framework\TestCase;

class EventVenueLocationSetterTest extends TestCase
{
    private InMemoryPlaceFinder $placeFinder;

    private EventVenueLocationSetter $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->placeFinder = new InMemoryPlaceFinder();
        $this->placeFinder->add('venue-reference', 'IFEMA, Madrid', 40.4680, -3.6160);

        $this->sut = new EventVenueLocationSetter($this->placeFinder);
    }

    public function testResolvesThePlaceAndStoresTheCoordinates(): void
    {
        $event = $this->event();

        $this->setVenueLocation($event, 'venue-reference');

        $this->assertSame('venue-reference', $event->venuePlaceReference());
        $this->assertSame('IFEMA, Madrid', $event->venueLabel());
        $this->assertSame(40.4680, $event->venueLatitude());
        $this->assertSame(-3.6160, $event->venueLongitude());
        $this->assertTrue($event->hasVenueLocation());
    }

    public function testDoesNotResolveAgainWhenTheReferenceDidNotChange(): void
    {
        $event = $this->event();

        $this->setVenueLocation($event, 'venue-reference');
        $this->setVenueLocation($event, 'venue-reference');

        $this->assertSame(1, $this->placeFinder->resolveCalls());
    }

    public function testClearsTheVenueWhenTheReferenceIsEmpty(): void
    {
        $event = $this->event();
        $this->setVenueLocation($event, 'venue-reference');

        $this->setVenueLocation($event, '');

        $this->assertNull($event->venuePlaceReference());
        $this->assertNull($event->venueLabel());
        $this->assertFalse($event->hasVenueLocation());
    }

    public function testDoesNothingWhenThereIsNoVenueAndNoReference(): void
    {
        $event = $this->event();

        $this->setVenueLocation($event, null);

        $this->assertNull($event->venuePlaceReference());
        $this->assertSame(0, $this->placeFinder->resolveCalls());
    }

    public function testThrowsWhenThePlaceCannotBeResolved(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->sut->resolve($this->event(), 'unknown-reference');
    }

    public function testLeavesTheEventUntouchedWhenThePlaceCannotBeResolved(): void
    {
        $event = $this->event();
        $this->setVenueLocation($event, 'venue-reference');
        $event->clearEvents();

        try {
            $this->sut->resolve($event, 'unknown-reference');
            $this->fail('The unresolvable reference should have been rejected.');
        } catch (\InvalidArgumentException) {
        }

        $this->assertSame('venue-reference', $event->venuePlaceReference());
        $this->assertSame(40.4680, $event->venueLatitude());
        $this->assertEmpty($event->recordedEvents());
    }

    private function setVenueLocation(Event $event, ?string $placeReference): void
    {
        $this->sut->apply($event, $this->sut->resolve($event, $placeReference));
    }

    private function event(): Event
    {
        return EventMother::from(name: 'Event', id: (string) EventId::generate());
    }
}
