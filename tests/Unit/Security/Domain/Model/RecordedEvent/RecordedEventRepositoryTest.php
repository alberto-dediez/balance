<?php

namespace App\Tests\Unit\Security\Domain\Model\RecordedEvent;

use App\Security\Domain\Model\User\UserId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Security\Domain\Model\RecordedEvent\RecordedEventFilter;
use App\Security\Domain\Model\RecordedEvent\RecordedEventRepository;

abstract class RecordedEventRepositoryTest extends KernelTestCase
{
    public function testAddRecordedEventToRepository()
    {   
        $repository = $this->createSUT();

        $event = RecordedEventMother::random();

        $this->assertNull($repository->find($event->id()));

        $repository->add($event);

        $recoverRecordedEvent = $repository->find($event->id());

        $this->assertNotNull($recoverRecordedEvent);
        $this->assertEquals($event->id(), $recoverRecordedEvent->id());
    }

    public function testRemoveRecordedEventToRepository()
    {   
        $repository = $this->createSUT();

        $event = RecordedEventMother::random();

        $repository->add($event);

        $recoverRecordedEvent = $repository->find($event->id());

        $this->assertNotNull($recoverRecordedEvent);
        $this->assertEquals($event->id(), $recoverRecordedEvent->id());

        $repository->remove($event);

        $this->assertNull($repository->find($event->id()));
    }

    public function testFindByFilter()
    {
        $repository = $this->createSUT();

        $userId = UserId::generate();

        $repository->add(RecordedEventMother::random());
        $repository->add(RecordedEventMother::random());
        $repository->add(RecordedEventMother::random());
        $repository->add(RecordedEventMother::random());
        $repository->add(RecordedEventMother::random());
        $repository->add(RecordedEventMother::fromUser($userId));

        $filter = new RecordedEventFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new RecordedEventFilter(
            userId: $userId
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    abstract protected function createSUT(): RecordedEventRepository;
}
