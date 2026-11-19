<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Model\Integration;

use App\Shared\Domain\Model\Integration\IntegrationPosition;
use PHPUnit\Framework\TestCase;

class IntegrationPositionTest extends TestCase
{
    public function testAdvanceMovesForwardWithGreaterSequence(): void
    {
        $position = new IntegrationPosition('waste', 'P1', 10, new \DateTimeImmutable('2026-11-19 10:00:00'));
        $now = new \DateTimeImmutable('2026-11-19 11:00:00');

        $position->advance(12, $now);

        $this->assertSame(12, $position->lastSequence());
        $this->assertEquals($now, $position->updatedAt());
    }

    public function testAdvanceIgnoresSmallerOrEqualSequence(): void
    {
        $updatedAt = new \DateTimeImmutable('2026-11-19 10:00:00');
        $position = new IntegrationPosition('waste', 'P1', 12, $updatedAt);

        $position->advance(11, new \DateTimeImmutable('2026-11-19 11:00:00'));
        $position->advance(12, new \DateTimeImmutable('2026-11-19 12:00:00'));

        $this->assertSame(12, $position->lastSequence());
        $this->assertEquals($updatedAt, $position->updatedAt());
    }
}
