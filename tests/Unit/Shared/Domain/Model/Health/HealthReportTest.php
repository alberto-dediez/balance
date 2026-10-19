<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Model\Health;

use App\Shared\Domain\Model\Health\HealthIndicatorResult;
use App\Shared\Domain\Model\Health\HealthReport;
use PHPUnit\Framework\TestCase;

class HealthReportTest extends TestCase
{
    public function testEmptyReportIsHealthy(): void
    {
        $report = new HealthReport([]);

        self::assertTrue($report->isHealthy());
        self::assertSame(['status' => 'ok', 'checks' => []], $report->toArray());
    }

    public function testAllHealthyResultsReportOk(): void
    {
        $report = new HealthReport([
            HealthIndicatorResult::healthy('database'),
            HealthIndicatorResult::healthy('queue'),
        ]);

        self::assertTrue($report->isHealthy());
        self::assertSame('ok', $report->toArray()['status']);
    }

    public function testOneUnhealthyResultReportsError(): void
    {
        $report = new HealthReport([
            HealthIndicatorResult::healthy('database'),
            HealthIndicatorResult::unhealthy('queue', 'transport unreachable'),
        ]);

        self::assertFalse($report->isHealthy());
        self::assertSame('error', $report->toArray()['status']);
    }

    public function testToArrayIndexesChecksByName(): void
    {
        $report = new HealthReport([
            HealthIndicatorResult::healthy('database'),
            HealthIndicatorResult::unhealthy('queue', 'transport unreachable'),
        ]);

        self::assertSame(
            ['status' => 'error', 'checks' => ['database' => 'ok', 'queue' => 'error']],
            $report->toArray(),
        );
    }
}
