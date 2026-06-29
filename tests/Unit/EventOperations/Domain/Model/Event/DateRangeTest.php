<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventOperations\Domain\Model\Event;

use App\EventOperations\Domain\Model\Event\DateRange;
use App\EventOperations\Domain\Model\Event\InvalidDateRangeException;
use PHPUnit\Framework\TestCase;

class DateRangeTest extends TestCase
{
    public function testCreateDateRangeWithValidRange(): void
    {
        $start = new \DateTimeImmutable('2026-01-01');
        $end = new \DateTimeImmutable('2026-01-02');

        $range = new DateRange($start, $end);

        $this->assertEquals($start, $range->startDate());
        $this->assertEquals($end, $range->endDate());
    }

    public function testCreateDateRangeWithSameStartAndEnd(): void
    {
        $date = new \DateTimeImmutable('2026-01-01');

        $range = new DateRange($date, $date);

        $this->assertEquals($date, $range->startDate());
        $this->assertEquals($date, $range->endDate());
    }

    public function testCreateDateRangeWithNullDates(): void
    {
        $range = new DateRange();

        $this->assertNull($range->startDate());
        $this->assertNull($range->endDate());
    }

    public function testCreateDateRangeWithStartButNoEnd(): void
    {
        $start = new \DateTimeImmutable('2026-01-01');

        $range = new DateRange($start, null);

        $this->assertEquals($start, $range->startDate());
        $this->assertNull($range->endDate());
    }

    public function testCreateDateRangeWithEndButNoStart(): void
    {
        $end = new \DateTimeImmutable('2026-01-01');

        $range = new DateRange(null, $end);

        $this->assertNull($range->startDate());
        $this->assertEquals($end, $range->endDate());
    }

    public function testCreateDateRangeWithStartAfterEndThrowsException(): void
    {
        $start = new \DateTimeImmutable('2026-01-02');
        $end = new \DateTimeImmutable('2026-01-01');

        $this->expectException(InvalidDateRangeException::class);

        new DateRange($start, $end);
    }

    public function testInvalidDateRangeExceptionIsDomainException(): void
    {
        $start = new \DateTimeImmutable('2026-01-02');
        $end = new \DateTimeImmutable('2026-01-01');

        try {
            new DateRange($start, $end);
            $this->fail('Expected InvalidDateRangeException');
        } catch (InvalidDateRangeException $e) {
            $this->assertInstanceOf(\DomainException::class, $e);
            $this->assertStringContainsString('02/01/2026', $e->getMessage());
            $this->assertStringContainsString('01/01/2026', $e->getMessage());
        }
    }

    public function testToString(): void
    {
        $start = new \DateTimeImmutable('2026-01-01');
        $end = new \DateTimeImmutable('2026-01-02');

        $range = new DateRange($start, $end);

        $this->assertEquals('01/01/2026 - 02/01/2026', (string) $range);
    }

    public function testToStringWithNullDates(): void
    {
        $range = new DateRange();

        $this->assertEquals('', (string) $range);
    }
}
