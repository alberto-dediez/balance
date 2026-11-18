<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Period;

use App\Shared\Application\Period\DailyPeriod;
use PHPUnit\Framework\TestCase;

class DailyPeriodTest extends TestCase
{
    public function testWeekGoesFromMondayToNextMonday(): void
    {
        foreach (['2026-10-22 15:00:00', '2026-10-19 00:00:00', '2026-10-25 23:59:00'] as $date) {
            $period = DailyPeriod::week(new \DateTimeImmutable($date));

            $this->assertSame(DailyPeriod::WEEK, $period->type);
            $this->assertSame('2026-10-19 00:00:00', $period->from?->format('Y-m-d H:i:s'));
            $this->assertSame('2026-10-26 00:00:00', $period->to?->format('Y-m-d H:i:s'));
            $this->assertSame('2026-10-25', $period->lastDay()?->format('Y-m-d'));
        }
    }

    public function testMonthGoesFromTheFirstToTheFirstOfTheNextMonth(): void
    {
        $october = DailyPeriod::month(new \DateTimeImmutable('2026-10-22 15:00:00'));
        $december = DailyPeriod::month(new \DateTimeImmutable('2026-12-15'));

        $this->assertSame(DailyPeriod::MONTH, $october->type);
        $this->assertSame('2026-10-01 00:00:00', $october->from?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-01 00:00:00', $october->to?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-31', $october->lastDay()?->format('Y-m-d'));
        $this->assertSame('2026-12-01', $december->from?->format('Y-m-d'));
        $this->assertSame('2027-01-01', $december->to?->format('Y-m-d'));
    }

    public function testPreviousAndNextShiftTheWindow(): void
    {
        $week = DailyPeriod::week(new \DateTimeImmutable('2026-10-22'));
        $month = DailyPeriod::month(new \DateTimeImmutable('2026-10-22'));
        $all = DailyPeriod::all();

        $this->assertSame('2026-10-12', $week->previous()->from?->format('Y-m-d'));
        $this->assertSame('2026-10-19', $week->previous()->to?->format('Y-m-d'));
        $this->assertSame('2026-10-26', $week->next()->from?->format('Y-m-d'));
        $this->assertSame(DailyPeriod::WEEK, $week->next()->type);
        $this->assertSame('2026-09-01', $month->previous()->from?->format('Y-m-d'));
        $this->assertSame('2026-11-01', $month->next()->from?->format('Y-m-d'));
        $this->assertSame('2026-12-01', $month->next()->to?->format('Y-m-d'));
        $this->assertSame(DailyPeriod::MONTH, $month->previous()->type);
        $this->assertSame($all, $all->previous());
        $this->assertSame($all, $all->next());
    }

    public function testContainsToday(): void
    {
        $now = new \DateTimeImmutable();
        $week = DailyPeriod::week($now);

        $this->assertTrue($week->containsToday($now));
        $this->assertFalse($week->previous()->containsToday($now));
        $this->assertTrue(DailyPeriod::month($now)->containsToday($now));
        $this->assertTrue(DailyPeriod::all()->containsToday($now));
    }

    public function testOfFallsBackToWeek(): void
    {
        $date = new \DateTimeImmutable('2026-10-14');

        $this->assertSame(DailyPeriod::WEEK, DailyPeriod::of('foo', $date)->type);
        $this->assertSame('2026-10-12', DailyPeriod::of('foo', $date)->from?->format('Y-m-d'));
        $this->assertSame(DailyPeriod::WEEK, DailyPeriod::of('week', $date)->type);
        $this->assertSame('2026-10-01', DailyPeriod::of('month', $date)->from?->format('Y-m-d'));
        $this->assertTrue(DailyPeriod::of('all', $date)->isAll());
        $this->assertNull(DailyPeriod::of('all', $date)->from);
        $this->assertNull(DailyPeriod::all()->lastDay());
    }
}
