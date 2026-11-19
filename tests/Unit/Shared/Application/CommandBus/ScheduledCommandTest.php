<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\CommandBus;

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

    public function testDailyAtWrapsCommandInDeferredWithoutDelay(): void
    {
        $sut = ScheduledCommand::dailyAt('task', new FakeScheduledInnerCommand(), '02:00');

        $deferred = $sut->command();
        $this->assertInstanceOf(DeferredCommand::class, $deferred);
        $this->assertNull($deferred->executeAt());
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

    /**
     * @return iterable<string, array{?string, string, bool}>
     */
    public static function dailyAtCases(): iterable
    {
        yield 'before today occurrence' => ['2026-11-18T03:20:05+00:00', '2026-11-19T03:19:00+00:00', false];
        yield 'at today occurrence' => ['2026-11-18T03:20:05+00:00', '2026-11-19T03:20:00+00:00', true];
        yield 'already ran today' => ['2026-11-19T03:20:05+00:00', '2026-11-19T03:21:00+00:00', false];
        yield 'ran exactly at the occurrence' => ['2026-11-19T03:20:00+00:00', '2026-11-19T03:21:00+00:00', false];
        yield 'missed ticks' => ['2026-11-18T03:20:05+00:00', '2026-11-19T05:00:00+00:00', true];
        yield 'never ran' => [null, '2026-11-19T10:00:00+00:00', false];
    }

    /**
     * @dataProvider dailyAtCases
     */
    public function testDailyAtIsDue(?string $lastRunAt, string $now, bool $expected): void
    {
        $sut = ScheduledCommand::dailyAt('task', new FakeScheduledInnerCommand(), '03:20');

        $this->assertSame($expected, $sut->isDue(new \DateTimeImmutable($now), null === $lastRunAt ? null : new \DateTimeImmutable($lastRunAt)));
    }

    public function testDailyAtIsDueUsesUtc(): void
    {
        $sut = ScheduledCommand::dailyAt('task', new FakeScheduledInnerCommand(), '03:20');
        $madrid = new \DateTimeZone('Europe/Madrid');

        $lastRunAt = new \DateTimeImmutable('2026-11-18T03:20:05+00:00');

        $this->assertFalse($sut->isDue((new \DateTimeImmutable('2026-11-19T03:19:00+00:00'))->setTimezone($madrid), $lastRunAt->setTimezone($madrid)));
        $this->assertTrue($sut->isDue((new \DateTimeImmutable('2026-11-19T03:20:00+00:00'))->setTimezone($madrid), $lastRunAt->setTimezone($madrid)));
    }

    // --- weeklyOn ---

    public function testWeeklyOnWrapsCommandInDeferredWithoutDelay(): void
    {
        $sut = ScheduledCommand::weeklyOn('task', new FakeScheduledInnerCommand(), 'sunday', '21:00');

        $deferred = $sut->command();
        $this->assertInstanceOf(DeferredCommand::class, $deferred);
        $this->assertNull($deferred->executeAt());
    }

    public function testWeeklyOnHasWeeklyInterval(): void
    {
        $sut = ScheduledCommand::weeklyOn('task', new FakeScheduledInnerCommand(), 'sunday', '21:00');

        $this->assertSame(7 * 24 * 60, $sut->intervalInMinutes());
    }

    public function testWeeklyOnDescribe(): void
    {
        $this->assertSame('weekly on sunday 21:00 UTC', ScheduledCommand::weeklyOn('task', new FakeScheduledInnerCommand(), 'sunday', '21:00')->describe());
    }

    public function testWeeklyOnRejectsInvalidDay(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ScheduledCommand::weeklyOn('task', new FakeScheduledInnerCommand(), 'funday', '21:00');
    }

    public function testWeeklyOnRejectsInvalidTime(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ScheduledCommand::weeklyOn('task', new FakeScheduledInnerCommand(), 'sunday', '25:00');
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function weeklyOnCases(): iterable
    {
        yield 'saturday before the occurrence' => ['2026-11-15T21:00:05+00:00', '2026-11-21T23:00:00+00:00', false];
        yield 'sunday at the occurrence' => ['2026-11-15T21:00:05+00:00', '2026-11-22T21:00:00+00:00', true];
        yield 'monday after running on sunday' => ['2026-11-22T21:00:05+00:00', '2026-11-23T09:00:00+00:00', false];
    }

    /**
     * @dataProvider weeklyOnCases
     */
    public function testWeeklyOnIsDue(string $lastRunAt, string $now, bool $expected): void
    {
        $sut = ScheduledCommand::weeklyOn('task', new FakeScheduledInnerCommand(), 'sunday', '21:00');

        $this->assertSame($expected, $sut->isDue(new \DateTimeImmutable($now), new \DateTimeImmutable($lastRunAt)));
    }

    public function testWaitsForItsTime(): void
    {
        $command = new FakeScheduledInnerCommand();

        $this->assertTrue(ScheduledCommand::dailyAt('task', $command, '02:00')->waitsForItsTime());
        $this->assertTrue(ScheduledCommand::weeklyOn('task', $command, 'sunday', '21:00')->waitsForItsTime());
        $this->assertFalse(ScheduledCommand::everyMinutes('task', $command, 5)->waitsForItsTime());
        $this->assertFalse(ScheduledCommand::everyHours('task', $command, 1)->waitsForItsTime());
    }
}
