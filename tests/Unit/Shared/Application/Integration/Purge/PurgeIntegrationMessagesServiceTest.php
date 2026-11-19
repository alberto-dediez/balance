<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Integration\Purge;

use App\Shared\Application\Integration\Purge\PurgeIntegrationMessagesRequest;
use App\Shared\Application\Integration\Purge\PurgeIntegrationMessagesService;
use App\Shared\Domain\Model\Integration\IntegrationOutboxFilter;
use App\Shared\Infrastructure\Persistence\InMemory\Repository\InMemoryIntegrationInboxRepository;
use App\Shared\Infrastructure\Persistence\InMemory\Repository\InMemoryIntegrationOutboxRepository;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Model\Integration\IntegrationInboxMessageMother;
use App\Tests\Unit\Shared\Domain\Model\Integration\IntegrationOutboxMessageMother;
use PHPUnit\Framework\TestCase;

class PurgeIntegrationMessagesServiceTest extends TestCase
{
    public function testItRemovesOldSentAndReceivedMessages(): void
    {
        $outbox = new InMemoryIntegrationOutboxRepository();
        $inbox = new InMemoryIntegrationInboxRepository();
        $now = new \DateTimeImmutable('2026-11-19 03:40:00');

        $oldSent = IntegrationOutboxMessageMother::sentAt($now->modify('-31 days'));
        $recentSent = IntegrationOutboxMessageMother::sentAt($now->modify('-29 days'));
        $oldPending = IntegrationOutboxMessageMother::pending(occurredOn: $now->modify('-60 days'));
        $outbox->add($oldSent);
        $outbox->add($recentSent);
        $outbox->add($oldPending);

        $oldReceived = IntegrationInboxMessageMother::processed(receivedAt: $now->modify('-31 days'));
        $recentReceived = IntegrationInboxMessageMother::processed(receivedAt: $now->modify('-29 days'));
        $inbox->add($oldReceived);
        $inbox->add($recentReceived);

        $sut = new PurgeIntegrationMessagesService($outbox, $inbox, 30);

        $response = $sut(new PurgeIntegrationMessagesRequest($now), UserMother::systemUser());

        $this->assertSame(1, $response->outboxRemoved);
        $this->assertSame(1, $response->inboxRemoved);

        $remaining = array_map(
            static fn($message): string => $message->messageId(),
            $outbox->list(new IntegrationOutboxFilter(limit: 0)),
        );
        $this->assertNotContains($oldSent->messageId(), $remaining);
        $this->assertContains($recentSent->messageId(), $remaining);
        $this->assertContains($oldPending->messageId(), $remaining);
        $this->assertFalse($inbox->has($oldReceived->messageId()));
        $this->assertTrue($inbox->has($recentReceived->messageId()));
    }
}
