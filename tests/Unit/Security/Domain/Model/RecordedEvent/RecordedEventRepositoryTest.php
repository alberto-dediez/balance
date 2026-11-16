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

    public function testFindByFilterByAssistant(): void
    {
        $repository = $this->createSUT();

        $repository->add(RecordedEventMother::random());
        $repository->add(RecordedEventMother::byAssistant('0190f3a4-1b2c-7d3e-8f40-123456789abc'));
        $repository->add(RecordedEventMother::byAssistant('0190f3a4-1b2c-7d3e-8f40-cba987654321'));

        $byAssistant = $repository->findByFilter(new RecordedEventFilter(byAssistant: true));
        $notByAssistant = $repository->findByFilter(new RecordedEventFilter(byAssistant: false));
        $ofExecution = $repository->findByFilter(new RecordedEventFilter(assistantExecutionId: '0190f3a4-1b2c-7d3e-8f40-123456789abc'));

        $this->assertCount(2, $byAssistant);
        $this->assertTrue($byAssistant[0]->isByAssistant());
        $this->assertCount(1, $notByAssistant);
        $this->assertFalse($notByAssistant[0]->isByAssistant());
        $this->assertCount(1, $ofExecution);
        $this->assertSame('0190f3a4-1b2c-7d3e-8f40-123456789abc', $ofExecution[0]->assistantExecutionId());
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

    public function testTotalResultsByFilterIgnoresPagination(): void
    {
        $repository = $this->createSUT();

        $repository->add(RecordedEventMother::random());
        $repository->add(RecordedEventMother::random());
        $repository->add(RecordedEventMother::random());
        $repository->add(RecordedEventMother::random());
        $repository->add(RecordedEventMother::random());
        $repository->add(RecordedEventMother::random());

        $filter = new RecordedEventFilter(
            limit: 2,
        );

        $this->assertCount(2, $repository->findByFilter($filter));

        $this->assertEquals(6, $repository->totalResultsByFilter($filter));
    }

    abstract protected function createSUT(): RecordedEventRepository;
}
