<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\CommandBus;

use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\ScheduledCommand;
use PHPUnit\Framework\TestCase;

class ScheduledCommandTest extends TestCase
{
    // --- everyMinutes ---

    public function testEveryMinutesRejectsNonPositiveInterval(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ScheduledCommand::everyMinutes('task', new FakeScheduledInnerCommand(), 0);
    }

    public function testEveryMinutesExposesIdAndInterval(): void
    {
        $sut = ScheduledCommand::everyMinutes('my-task', new FakeScheduledInnerCommand(), 15);

        $this->assertSame('my-task', $sut->id());
        $this->assertSame(15, $sut->intervalInMinutes());
    }

    public function testEveryMinutesWrapsCommandInDeferred(): void
    {
        $inner = new FakeScheduledInnerCommand();
        $sut = ScheduledCommand::everyMinutes('task', $inner, 15);

        $deferred = $sut->command();
        $this->assertInstanceOf(DeferredCommand::class, $deferred);
        /* @var DeferredCommand $deferred */
        $this->assertSame($inner, $deferred->command());
    }

    public function testEveryMinutesAcceptsAnyPositiveInterval(): void
    {
        $sut = ScheduledCommand::everyMinutes('task', new FakeScheduledInnerCommand(), 7);

        $this->assertSame(7, $sut->intervalInMinutes());
    }

    public function testEveryMinutesDescribe(): void
    {
        $this->assertSame('every 15 minutes', ScheduledCommand::everyMinutes('task', new FakeScheduledInnerCommand(), 15)->describe());
    }

    // --- everyHours ---

    public function testEveryHoursRejectsNonPositiveValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ScheduledCommand::everyHours('task', new FakeScheduledInnerCommand(), 0);
    }

    public function testEveryHoursWrapsCommandInDeferred(): void
    {
        $inner = new FakeScheduledInnerCommand();
        $sut = ScheduledCommand::everyHours('task', $inner, 2);

        $deferred = $sut->command();
        $this->assertInstanceOf(DeferredCommand::class, $deferred);
        /* @var DeferredCommand $deferred */
        $this->assertSame($inner, $deferred->command());
    }

    public function testEveryHoursDescribeSingular(): void
    {
        $this->assertSame('every 1 hour', ScheduledCommand::everyHours('task', new FakeScheduledInnerCommand(), 1)->describe());
    }

    public function testEveryHoursDescribePlural(): void
    {
        $this->assertSame('every 3 hours', ScheduledCommand::everyHours('task', new FakeScheduledInnerCommand(), 3)->describe());
    }

    // --- dailyAt ---

    public function testDailyAtRejectsInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ScheduledCommand::dailyAt('task', new FakeScheduledInnerCommand(), '2:00');
    }

    public function testDailyAtWrapsCommandInDeferredWithExecuteAt(): void
    {
        $before = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $sut = ScheduledCommand::dailyAt('task', new FakeScheduledInnerCommand(), '02:00');

        $deferred = $sut->command();
        $this->assertInstanceOf(DeferredCommand::class, $deferred);

        /** @var DeferredCommand $deferred */
        $executeAt = $deferred->executeAt();
        $this->assertNotNull($executeAt);
        $this->assertGreaterThan($before, $executeAt);

        $executeAtUtc = $executeAt->setTimezone(new \DateTimeZone('UTC'));
        $this->assertSame('02:00', $executeAtUtc->format('H:i'));
    }

    public function testDailyAtAcceptsAnyValidTimeRegardlessOfTickAlignment(): void
    {
        $sut = ScheduledCommand::dailyAt('task', new FakeScheduledInnerCommand(), '02:03');

        $this->assertInstanceOf(ScheduledCommand::class, $sut);
    }

    public function testDailyAtDescribe(): void
    {
        $this->assertSame('daily at 02:00 UTC', ScheduledCommand::dailyAt('task', new FakeScheduledInnerCommand(), '02:00')->describe());
    }

    // --- isDue ---

    public function testIsDueWhenNeverRunBefore(): void
    {
        $sut = ScheduledCommand::everyMinutes('task', new FakeScheduledInnerCommand(), 15);

        $this->assertTrue($sut->isDue(new \DateTimeImmutable('now'), null));
    }

    public function testIsDueWhenIntervalHasElapsed(): void
    {
        $sut = ScheduledCommand::everyMinutes('task', new FakeScheduledInnerCommand(), 15);

        $lastRunAt = new \DateTimeImmutable('2026-06-13T10:00:00+00:00');
        $now = new \DateTimeImmutable('2026-06-13T10:15:00+00:00');

        $this->assertTrue($sut->isDue($now, $lastRunAt));
    }

    public function testIsDueWhenIntervalHasElapsedByMoreThanRequired(): void
    {
        // Missed ticks are tolerated — still fires on the next run.
        $sut = ScheduledCommand::everyMinutes('task', new FakeScheduledInnerCommand(), 15);

        $lastRunAt = new \DateTimeImmutable('2026-06-12T10:00:00+00:00');
        $now = new \DateTimeImmutable('2026-06-13T10:00:00+00:00');

        $this->assertTrue($sut->isDue($now, $lastRunAt));
    }

    public function testIsNotDueWhenIntervalHasNotElapsed(): void
    {
        $sut = ScheduledCommand::everyMinutes('task', new FakeScheduledInnerCommand(), 15);

        $lastRunAt = new \DateTimeImmutable('2026-06-13T10:00:00+00:00');
        $now = new \DateTimeImmutable('2026-06-13T10:10:00+00:00');

        $this->assertFalse($sut->isDue($now, $lastRunAt));
    }

    public function testIsNotDueWhenJustRun(): void
    {
        // Manual re-run of the cron does not double-dispatch.
        $sut = ScheduledCommand::everyMinutes('task', new FakeScheduledInnerCommand(), 15);

        $now = new \DateTimeImmutable('2026-06-13T10:00:00+00:00');

        $this->assertFalse($sut->isDue($now, $now));
    }

    public function testDailyAtIsDueAfter24Hours(): void
    {
        $sut = ScheduledCommand::dailyAt('task', new FakeScheduledInnerCommand(), '02:00');

        $lastRunAt = new \DateTimeImmutable('2026-06-13T00:00:00+00:00');
        $now = new \DateTimeImmutable('2026-06-14T00:00:00+00:00');

        $this->assertTrue($sut->isDue($now, $lastRunAt));
    }

    public function testDailyAtIsNotDueBefore24Hours(): void
    {
        $sut = ScheduledCommand::dailyAt('task', new FakeScheduledInnerCommand(), '02:00');

        $lastRunAt = new \DateTimeImmutable('2026-06-13T00:00:00+00:00');
        $now = new \DateTimeImmutable('2026-06-13T23:59:00+00:00');

        $this->assertFalse($sut->isDue($now, $lastRunAt));
    }
}

class FakeScheduledInnerCommand implements Command {}
