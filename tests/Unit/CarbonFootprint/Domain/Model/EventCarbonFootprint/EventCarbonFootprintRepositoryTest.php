<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintFilter;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class EventCarbonFootprintRepositoryTest extends KernelTestCase
{
    public function testAddEventCarbonFootprintToRepository(): void
    {
        $repository = $this->createSUT();

        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();

        $this->assertNull($repository->find($eventCarbonFootprint->id()));

        $repository->add($eventCarbonFootprint);

        $recoverEventCarbonFootprint = $repository->find($eventCarbonFootprint->id());

        $this->assertNotNull($recoverEventCarbonFootprint);
        $this->assertEquals($eventCarbonFootprint->id(), $recoverEventCarbonFootprint->id());
    }

    public function testRemoveEventCarbonFootprintToRepository(): void
    {
        $repository = $this->createSUT();

        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();

        $repository->add($eventCarbonFootprint);

        $recoverEventCarbonFootprint = $repository->find($eventCarbonFootprint->id());

        $this->assertNotNull($recoverEventCarbonFootprint);
        $this->assertEquals($eventCarbonFootprint->id(), $recoverEventCarbonFootprint->id());

        $repository->remove($eventCarbonFootprint);

        $this->assertNull($repository->find($eventCarbonFootprint->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(EventCarbonFootprintMother::fromEventIdAndAccount((string) EventId::generate(), 'account-one'));
        $repository->add(EventCarbonFootprintMother::fromEventIdAndAccount((string) EventId::generate(), 'account-one'));
        $repository->add(EventCarbonFootprintMother::fromEventIdAndAccount((string) EventId::generate(), 'account-two'));

        $filter = new EventCarbonFootprintFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(3, $resultUnfiltered);

        $filter = new EventCarbonFootprintFilter(
            accountId: new AccountId('account-one'),
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(2, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(EventCarbonFootprintMother::random());
        $repository->add(EventCarbonFootprintMother::random());
        $repository->add(EventCarbonFootprintMother::random());

        $filter = new EventCarbonFootprintFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(3, $resultUnfiltered);

        $filter = new EventCarbonFootprintFilter(
            limit: 0,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(3, $result);
    }

    public function testFindByEventId(): void
    {
        $repository = $this->createSUT();

        $eventId = (string) EventId::generate();
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventId($eventId);

        $repository->add($eventCarbonFootprint);

        $recoverEventCarbonFootprint = $repository->findByEventId(new EventId($eventId));

        $this->assertNotNull($recoverEventCarbonFootprint);
        $this->assertEquals($eventCarbonFootprint->id(), $recoverEventCarbonFootprint->id());
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();

        $repository->add($eventCarbonFootprint);

        $recoverEventCarbonFootprint = $repository->find($eventCarbonFootprint->id());

        $this->assertNotNull($recoverEventCarbonFootprint);

        $recoverEventCarbonFootprint->remove();

        $repository->add($eventCarbonFootprint);

        $this->assertNull($repository->find($eventCarbonFootprint->id()));
    }

    abstract protected function createSUT(): EventCarbonFootprintRepository;
}
