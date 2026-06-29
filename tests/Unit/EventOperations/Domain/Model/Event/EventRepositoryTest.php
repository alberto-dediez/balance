<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Domain\Model\Event;

use App\EventOperations\Domain\Model\Event\EventFilter;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\EventOperations\Domain\Model\Event\EventSector;
use App\EventOperations\Domain\Model\Event\EventSize;
use App\EventOperations\Domain\Model\Event\EventTypology;
use App\Property\Domain\Model\Destination\DestinationId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class EventRepositoryTest extends KernelTestCase
{
    public function testAddEventToRepository(): void
    {
        $repository = $this->createSUT();

        $event = EventMother::registeredEvent();

        $this->assertNull($repository->find($event->id()));

        $repository->add($event);

        $recoveredEvent = $repository->find($event->id());

        $this->assertNotNull($recoveredEvent);
        $this->assertEquals($event->id(), $recoveredEvent->id());
    }

    public function testRemoveEventFromRepository(): void
    {
        $repository = $this->createSUT();

        $event = EventMother::registeredEvent();

        $repository->add($event);

        $recoveredEvent = $repository->find($event->id());

        $this->assertNotNull($recoveredEvent);
        $this->assertEquals($event->id(), $recoveredEvent->id());

        $repository->remove($event);

        $this->assertNull($repository->find($event->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(EventMother::random());
        $repository->add(EventMother::random());
        $repository->add(EventMother::random());
        $repository->add(EventMother::random());
        $repository->add(EventMother::random());
        $repository->add(EventMother::fromName('Evento específico'));

        $filter = new EventFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new EventFilter(
            name: 'Evento específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(EventMother::random());
        $repository->add(EventMother::random());
        $repository->add(EventMother::random());
        $repository->add(EventMother::random());
        $repository->add(EventMother::random());
        $repository->add(EventMother::fromName('Evento específico'));

        $filter = new EventFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $event = EventMother::registeredEvent();

        $repository->add($event);

        $recoveredEvent = $repository->find($event->id());

        $this->assertNotNull($recoveredEvent);

        $recoveredEvent->remove();

        $repository->add($event);

        $this->assertNull($repository->find($event->id()));
    }

    public function testFindByFilterByLocation(): void
    {
        $repository = $this->createSUT();

        $repository->add(EventMother::fromFull(
            name: 'Event A',
            id: (string) \App\EventOperations\Domain\Model\Event\EventId::generate(),
            locationId: 'location-1',
        ));
        $repository->add(EventMother::fromFull(
            name: 'Event B',
            id: (string) \App\EventOperations\Domain\Model\Event\EventId::generate(),
            locationId: 'location-2',
        ));

        $filter = new EventFilter(
            location: new DestinationId('location-1'),
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
        $this->assertEquals('Event A', $result[0]->name());
    }

    public function testFindByFilterBySize(): void
    {
        $repository = $this->createSUT();

        $repository->add(EventMother::fromFull(
            name: 'Event A',
            id: (string) \App\EventOperations\Domain\Model\Event\EventId::generate(),
            size: EventSize::Small,
        ));
        $repository->add(EventMother::fromFull(
            name: 'Event B',
            id: (string) \App\EventOperations\Domain\Model\Event\EventId::generate(),
            size: EventSize::Large,
        ));

        $filter = new EventFilter(
            size: EventSize::Large,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
        $this->assertEquals('Event B', $result[0]->name());
    }

    public function testFindByFilterBySector(): void
    {
        $repository = $this->createSUT();

        $repository->add(EventMother::fromFull(
            name: 'Event A',
            id: (string) \App\EventOperations\Domain\Model\Event\EventId::generate(),
            sector: EventSector::Business,
        ));
        $repository->add(EventMother::fromFull(
            name: 'Event B',
            id: (string) \App\EventOperations\Domain\Model\Event\EventId::generate(),
            sector: EventSector::Music,
        ));

        $filter = new EventFilter(
            sector: EventSector::Music,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
        $this->assertEquals('Event B', $result[0]->name());
    }

    public function testFindByFilterByTypology(): void
    {
        $repository = $this->createSUT();

        $repository->add(EventMother::fromFull(
            name: 'Event A',
            id: (string) \App\EventOperations\Domain\Model\Event\EventId::generate(),
            typology: EventTypology::Local,
        ));
        $repository->add(EventMother::fromFull(
            name: 'Event B',
            id: (string) \App\EventOperations\Domain\Model\Event\EventId::generate(),
            typology: EventTypology::International,
        ));

        $filter = new EventFilter(
            typology: EventTypology::International,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
        $this->assertEquals('Event B', $result[0]->name());
    }

    abstract protected function createSUT(): EventRepository;
}
