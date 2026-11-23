<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Model\Integration;

use App\Shared\Domain\Model\Integration\IntegrationOutboxFilter;
use App\Shared\Domain\Model\Integration\IntegrationOutboxRepository;
use App\Shared\Domain\Model\Integration\IntegrationOutboxStatus;
use PHPUnit\Framework\TestCase;

abstract class IntegrationOutboxRepositoryTest extends TestCase
{
    abstract protected function createSUT(): IntegrationOutboxRepository;

    public function testAddAssignsIncrementalSequence(): void
    {
        $repository = $this->createSUT();
        $first = IntegrationOutboxMessageMother::pending();
        $second = IntegrationOutboxMessageMother::pending();

        $repository->add($first);
        $repository->add($second);

        $this->assertNotNull($first->sequence());
        $this->assertGreaterThan($first->sequence(), $second->sequence());
    }

    public function testPendingReturnsUnsentMessagesInSequenceOrder(): void
    {
        $repository = $this->createSUT();
        $first = IntegrationOutboxMessageMother::pending();
        $sent = IntegrationOutboxMessageMother::pending();
        $third = IntegrationOutboxMessageMother::pending();
        $fourth = IntegrationOutboxMessageMother::pending();
        $repository->add($first);
        $repository->add($sent);
        $repository->add($third);
        $repository->add($fourth);

        $sent->markSent(new \DateTimeImmutable('NOW'));
        $repository->save($sent);

        $pending = $repository->pending(2);

        $this->assertCount(2, $pending);
        $this->assertSame($first->messageId(), $pending[0]->messageId());
        $this->assertSame($third->messageId(), $pending[1]->messageId());
    }

    public function testOldestPendingOccurredOn(): void
    {
        $repository = $this->createSUT();
        $this->assertNull($repository->oldestPendingOccurredOn());

        $oldest = new \DateTimeImmutable('2026-11-19 09:00:00');
        $repository->add(IntegrationOutboxMessageMother::pending(occurredOn: new \DateTimeImmutable('2026-11-19 10:00:00')));
        $repository->add(IntegrationOutboxMessageMother::pending(occurredOn: $oldest));
        $repository->add(IntegrationOutboxMessageMother::sentAt(new \DateTimeImmutable('2026-11-19 08:00:00')));

        $this->assertEquals($oldest, $repository->oldestPendingOccurredOn());
    }

    public function testListAppliesFilter(): void
    {
        $repository = $this->createSUT();
        $repository->add(IntegrationOutboxMessageMother::pending('platform.pinged'));
        $repository->add(IntegrationOutboxMessageMother::pending('property.registered'));
        $repository->add(IntegrationOutboxMessageMother::sentAt(new \DateTimeImmutable('NOW')));

        $this->assertCount(3, $repository->list(new IntegrationOutboxFilter()));
        $this->assertCount(2, $repository->list(new IntegrationOutboxFilter(status: IntegrationOutboxStatus::Pending)));
        $this->assertCount(1, $repository->list(new IntegrationOutboxFilter(status: IntegrationOutboxStatus::Sent)));
        $this->assertCount(1, $repository->list(new IntegrationOutboxFilter(type: 'property.registered')));
        $this->assertCount(2, $repository->list(new IntegrationOutboxFilter(limit: 2)));
    }

    public function testListOrdersBySequenceDescending(): void
    {
        $repository = $this->createSUT();
        $first = IntegrationOutboxMessageMother::pending();
        $second = IntegrationOutboxMessageMother::pending();
        $repository->add($first);
        $repository->add($second);

        $list = $repository->list(new IntegrationOutboxFilter());

        $this->assertSame($second->messageId(), $list[0]->messageId());
        $this->assertSame($first->messageId(), $list[1]->messageId());
    }

    public function testPurgeSentBeforeKeepsPendingAndRecent(): void
    {
        $repository = $this->createSUT();
        $now = new \DateTimeImmutable('2026-11-19 03:40:00');
        $old = IntegrationOutboxMessageMother::sentAt($now->modify('-31 days'));
        $recent = IntegrationOutboxMessageMother::sentAt($now->modify('-29 days'));
        $pending = IntegrationOutboxMessageMother::pending(occurredOn: $now->modify('-60 days'));
        $repository->add($old);
        $repository->add($recent);
        $repository->add($pending);

        $removed = $repository->purgeSentBefore($now->modify('-30 days'));

        $this->assertSame(1, $removed);
        $remaining = array_map(
            static fn($message): string => $message->messageId(),
            $repository->list(new IntegrationOutboxFilter(limit: 0)),
        );
        $this->assertNotContains($old->messageId(), $remaining);
        $this->assertContains($recent->messageId(), $remaining);
        $this->assertContains($pending->messageId(), $remaining);
    }
}
