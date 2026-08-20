<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\RecordedEvent;

use App\Security\Domain\Model\RecordedEvent\RecordedEventFilter;
use App\Security\Domain\Model\RecordedEvent\RecordedEventRepository;
use App\Security\Domain\Model\User\UserId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class RecordedEventRepositoryTest extends KernelTestCase
{
    public function testAddRecordedEventToRepository(): void
    {
        $repository = $this->createSUT();

        $event = RecordedEventMother::random();

        $this->assertNull($repository->find($event->id()));

        $repository->add($event);

        $recoverRecordedEvent = $repository->find($event->id());

        $this->assertNotNull($recoverRecordedEvent);
        $this->assertEquals($event->id(), $recoverRecordedEvent->id());
    }

    public function testRemoveRecordedEventToRepository(): void
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

    public function testFindByFilter(): void
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
            userId: $userId,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterByImpersonator(): void
    {
        $repository = $this->createSUT();

        $impersonatorId = UserId::generate();

        $repository->add(RecordedEventMother::random());
        $repository->add(RecordedEventMother::random());
        $repository->add(RecordedEventMother::impersonated(UserId::generate(), $impersonatorId));

        $filter = new RecordedEventFilter(
            impersonatorId: $impersonatorId,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
        $this->assertTrue($result[0]->isImpersonated());
        $this->assertEquals($impersonatorId, $result[0]->impersonatorId());
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
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
            limit: 0,
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    abstract protected function createSUT(): RecordedEventRepository;
}
