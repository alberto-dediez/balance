<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\ExchangeRate;

use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateSyncFailed;
use PHPUnit\Framework\TestCase;

class ExchangeRateSyncFailedTest extends TestCase
{
    public function testPayloadAndRebuildFromPayloadAreSymmetric(): void
    {
        $event = new ExchangeRateSyncFailed(
            reason: 'The ECB exchange rates could not be downloaded.',
            attemptedAt: new \DateTimeImmutable('2026-10-14T17:00:00+00:00'),
        );

        $rebuilt = ExchangeRateSyncFailed::rebuildFromPayload($event->id(), $event->occurredOn(), $event->payload());

        $this->assertSame($event->payload(), $rebuilt->payload());
        $this->assertSame('The ECB exchange rates could not be downloaded.', $rebuilt->reason());
        $this->assertEquals(new \DateTimeImmutable('2026-10-14T17:00:00+00:00'), $rebuilt->attemptedAt());
        $this->assertNull($rebuilt->aggregateRootId());
    }
}
