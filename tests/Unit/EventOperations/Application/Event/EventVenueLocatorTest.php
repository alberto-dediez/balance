<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Application\Event;

use App\EventOperations\Application\Event\EventVenueLocator;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\EventOperations\Domain\Model\Event\EventMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use PHPUnit\Framework\TestCase;

class EventVenueLocatorTest extends TestCase
{
    private InMemoryPropertyRepository $propertyRepository;

    private EventVenueLocator $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->propertyRepository = new InMemoryPropertyRepository();
        $this->sut = new EventVenueLocator($this->propertyRepository);
    }

    public function testUsesTheCoordinatesExplicitlySetOnTheEvent(): void
    {
        $event = EventMother::from(name: 'Event', id: (string) EventId::generate());
        $event->setVenueLocation('place-reference', 'IFEMA, Madrid', 40.4680, -3.6160);

        $point = $this->sut->locate($event);

        $this->assertNotNull($point);
        $this->assertSame(40.4680, $point->latitude);
        $this->assertSame(-3.6160, $point->longitude);
    }

    public function testFallsBackToThePropertyOfTheEvent(): void
    {
        $propertyId = (string) PropertyId::generate();

        $this->propertyRepository->add(PropertyMother::from(
            propertyId: new PropertyId($propertyId),
            info: new PropertyInfo(longitude: '2.1686', latitude: '41.3874'),
        ));

        $event = EventMother::from(name: 'Event', id: 'event-id', propertyId: $propertyId);

        $point = $this->sut->locate($event);

        $this->assertNotNull($point);
        $this->assertSame(41.3874, $point->latitude);
        $this->assertSame(2.1686, $point->longitude);
    }

    public function testTheEventCoordinatesWinOverTheProperty(): void
    {
        $propertyId = (string) PropertyId::generate();

        $this->propertyRepository->add(PropertyMother::from(
            propertyId: new PropertyId($propertyId),
            info: new PropertyInfo(longitude: '2.1686', latitude: '41.3874'),
        ));

        $event = EventMother::from(name: 'Event', id: 'event-id', propertyId: $propertyId);
        $event->setVenueLocation('place-reference', 'IFEMA, Madrid', 40.4680, -3.6160);

        $point = $this->sut->locate($event);

        $this->assertNotNull($point);
        $this->assertSame(40.4680, $point->latitude);
    }

    public function testReturnsNullWhenThePropertyHasNoUsableCoordinates(): void
    {
        $propertyId = (string) PropertyId::generate();

        $this->propertyRepository->add(PropertyMother::from(
            propertyId: new PropertyId($propertyId),
            info: new PropertyInfo(longitude: '', latitude: 'not-a-number'),
        ));

        $this->assertNull($this->sut->locate(EventMother::from(name: 'Event', id: 'event-id', propertyId: $propertyId)));
    }

    public function testReturnsNullWhenThereIsNoEvent(): void
    {
        $this->assertNull($this->sut->locate(null));
    }
}
