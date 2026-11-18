<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Assistant;

use App\Shared\Application\Assistant\ToolContext;
use PHPUnit\Framework\TestCase;

class ToolContextTest extends TestCase
{
    public function testTheDayOfATimestampIsTheOneOfTheContextTimezone(): void
    {
        $context = new ToolContext(new \DateTimeImmutable('2026-11-11 10:00:00'), new \DateTimeZone('Europe/Madrid'));

        $this->assertSame('2026-11-12', $context->date(new \DateTimeImmutable('2026-11-11 23:30:00', new \DateTimeZone('UTC'))));
    }

    public function testADateWithoutTimeKeepsItsDay(): void
    {
        $context = new ToolContext(new \DateTimeImmutable('2026-11-11 10:00:00'), new \DateTimeZone('America/New_York'));

        $this->assertSame('2026-11-11', $context->date(new \DateTimeImmutable('2026-11-11 00:00:00', new \DateTimeZone('UTC'))));
    }

    public function testNoDateIsNull(): void
    {
        $context = new ToolContext(new \DateTimeImmutable('2026-11-11 10:00:00'), new \DateTimeZone('Europe/Madrid'));

        $this->assertNull($context->date(null));
    }
}
