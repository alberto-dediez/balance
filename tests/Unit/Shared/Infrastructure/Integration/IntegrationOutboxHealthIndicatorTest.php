<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Integration;

use App\Shared\Infrastructure\Integration\IntegrationOutboxHealthIndicator;
use App\Shared\Infrastructure\Persistence\InMemory\Repository\InMemoryIntegrationOutboxRepository;
use App\Tests\Unit\Shared\Domain\Model\Integration\IntegrationOutboxMessageMother;
use PHPUnit\Framework\TestCase;

class IntegrationOutboxHealthIndicatorTest extends TestCase
{
    public function testDisabledIsHealthy(): void
    {
        $outbox = new InMemoryIntegrationOutboxRepository();
        $outbox->add(IntegrationOutboxMessageMother::pending(occurredOn: new \DateTimeImmutable('-1 day')));

        $result = (new IntegrationOutboxHealthIndicator(IntegrationSettingsMother::disabled(), $outbox, 10))->check();

        $this->assertTrue($result->healthy);
        $this->assertSame('integration_outbox', $result->name);
    }

    public function testWithoutPendingMessagesIsHealthy(): void
    {
        $outbox = new InMemoryIntegrationOutboxRepository();
        $outbox->add(IntegrationOutboxMessageMother::sentAt(new \DateTimeImmutable('-1 day')));

        $result = (new IntegrationOutboxHealthIndicator(IntegrationSettingsMother::enabled(), $outbox, 10))->check();

        $this->assertTrue($result->healthy);
    }

    public function testRecentPendingMessageIsHealthy(): void
    {
        $outbox = new InMemoryIntegrationOutboxRepository();
        $outbox->add(IntegrationOutboxMessageMother::pending(occurredOn: new \DateTimeImmutable('-5 minutes', new \DateTimeZone('UTC'))));

        $result = (new IntegrationOutboxHealthIndicator(IntegrationSettingsMother::enabled(), $outbox, 10))->check();

        $this->assertTrue($result->healthy);
    }

    public function testOldPendingMessageIsUnhealthy(): void
    {
        $outbox = new InMemoryIntegrationOutboxRepository();
        $outbox->add(IntegrationOutboxMessageMother::pending(occurredOn: new \DateTimeImmutable('-11 minutes', new \DateTimeZone('UTC'))));

        $result = (new IntegrationOutboxHealthIndicator(IntegrationSettingsMother::enabled(), $outbox, 10))->check();

        $this->assertFalse($result->healthy);
        $this->assertSame('outbox messages waiting too long', $result->detail);
    }
}
