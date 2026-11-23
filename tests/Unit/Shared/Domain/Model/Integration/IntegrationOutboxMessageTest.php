<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Model\Integration;

use PHPUnit\Framework\TestCase;

class IntegrationOutboxMessageTest extends TestCase
{
    public function testRoutingKeyCombinesTypeAndVersion(): void
    {
        $message = IntegrationOutboxMessageMother::from(type: 'property.registered', version: 2);

        $this->assertSame('property.registered.v2', $message->routingKey());
    }

    public function testMarkSentClearsLastError(): void
    {
        $message = IntegrationOutboxMessageMother::pending();
        $message->markFailedAttempt('Broker unreachable');
        $now = new \DateTimeImmutable('2026-11-19 10:00:00');

        $message->markSent($now);

        $this->assertTrue($message->isSent());
        $this->assertEquals($now, $message->sentAt());
        $this->assertNull($message->lastError());
        $this->assertSame(1, $message->attempts());
    }

    public function testMarkFailedAttemptIncrementsAttemptsAndTruncatesError(): void
    {
        $message = IntegrationOutboxMessageMother::pending();

        $message->markFailedAttempt('First');
        $message->markFailedAttempt(str_repeat('x', 1500));

        $this->assertSame(2, $message->attempts());
        $this->assertSame(1000, mb_strlen((string) $message->lastError()));
        $this->assertFalse($message->isSent());
    }
}
