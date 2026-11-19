<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Model\Integration;

use App\Shared\Domain\Model\Integration\IntegrationInboxFilter;
use App\Shared\Domain\Model\Integration\IntegrationInboxRepository;
use App\Shared\Domain\Model\Integration\IntegrationInboxStatus;
use PHPUnit\Framework\TestCase;

abstract class IntegrationInboxRepositoryTest extends TestCase
{
    abstract protected function createSUT(): IntegrationInboxRepository;

    public function testAddAndHas(): void
    {
        $repository = $this->createSUT();
        $message = IntegrationInboxMessageMother::processed();

        $this->assertFalse($repository->has($message->messageId()));

        $repository->add($message);

        $this->assertTrue($repository->has($message->messageId()));
    }

    public function testFind(): void
    {
        $repository = $this->createSUT();
        $message = IntegrationInboxMessageMother::from(status: IntegrationInboxStatus::SkippedStale);
        $repository->add(IntegrationInboxMessageMother::processed());

        $this->assertNull($repository->find($message->messageId()));

        $repository->add($message);

        $this->assertSame($message, $repository->find($message->messageId()));
    }

    public function testListAppliesFilter(): void
    {
        $repository = $this->createSUT();
        $repository->add(IntegrationInboxMessageMother::from(source: 'waste'));
        $repository->add(IntegrationInboxMessageMother::from(source: 'balance', type: 'property.registered'));
        $repository->add(IntegrationInboxMessageMother::from(source: 'waste', status: IntegrationInboxStatus::SkippedStale));

        $this->assertCount(3, $repository->list(new IntegrationInboxFilter()));
        $this->assertCount(2, $repository->list(new IntegrationInboxFilter(source: 'waste')));
        $this->assertCount(1, $repository->list(new IntegrationInboxFilter(type: 'property.registered')));
        $this->assertCount(1, $repository->list(new IntegrationInboxFilter(status: IntegrationInboxStatus::SkippedStale)));
        $this->assertCount(2, $repository->list(new IntegrationInboxFilter(limit: 2)));
    }

    public function testListOrdersByReceivedAtDescending(): void
    {
        $repository = $this->createSUT();
        $older = IntegrationInboxMessageMother::processed(receivedAt: new \DateTimeImmutable('2026-11-19 09:00:00'));
        $newer = IntegrationInboxMessageMother::processed(receivedAt: new \DateTimeImmutable('2026-11-19 10:00:00'));
        $repository->add($older);
        $repository->add($newer);

        $list = $repository->list(new IntegrationInboxFilter());

        $this->assertSame($newer->messageId(), $list[0]->messageId());
        $this->assertSame($older->messageId(), $list[1]->messageId());
    }

    public function testPurgeReceivedBefore(): void
    {
        $repository = $this->createSUT();
        $now = new \DateTimeImmutable('2026-11-19 03:40:00');
        $old = IntegrationInboxMessageMother::processed(receivedAt: $now->modify('-31 days'));
        $recent = IntegrationInboxMessageMother::processed(receivedAt: $now->modify('-29 days'));
        $repository->add($old);
        $repository->add($recent);

        $removed = $repository->purgeReceivedBefore($now->modify('-30 days'));

        $this->assertSame(1, $removed);
        $this->assertFalse($repository->has($old->messageId()));
        $this->assertTrue($repository->has($recent->messageId()));
    }
}
