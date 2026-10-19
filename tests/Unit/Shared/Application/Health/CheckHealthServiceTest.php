<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Health;

use App\Shared\Application\Health\CheckHealthService;
use App\Shared\Domain\Model\Health\HealthIndicator;
use App\Shared\Domain\Model\Health\HealthIndicatorResult;
use App\Shared\Infrastructure\Health\InMemoryHealthIndicator;
use PHPUnit\Framework\TestCase;

class CheckHealthServiceTest extends TestCase
{
    public function testHealthyIndicatorsReturnHealthyReport(): void
    {
        $service = new CheckHealthService([new InMemoryHealthIndicator(), new InMemoryHealthIndicator()]);

        $report = $service();

        self::assertTrue($report->isHealthy());
        self::assertCount(2, $report->results());
        self::assertSame('ok', $report->toArray()['status']);
    }

    public function testUnhealthyIndicatorReportsErrorOnlyOnIt(): void
    {
        $failing = new InMemoryHealthIndicator();
        $failing->markUnhealthy();

        $service = new CheckHealthService([new InMemoryHealthIndicator(), $failing]);

        $report = $service();
        $results = $report->results();

        self::assertFalse($report->isHealthy());
        self::assertSame('error', $report->toArray()['status']);
        self::assertTrue($results[0]->healthy);
        self::assertFalse($results[1]->healthy);
        self::assertSame('forced failure', $results[1]->detail);
    }

    public function testThrowingIndicatorBecomesUnhealthy(): void
    {
        $throwing = new class implements HealthIndicator {
            public function name(): string
            {
                return 'throwing';
            }

            public function check(): HealthIndicatorResult
            {
                throw new \RuntimeException('Unexpected failure');
            }
        };

        $service = new CheckHealthService([new InMemoryHealthIndicator(), $throwing]);

        $report = $service();
        $results = $report->results();

        self::assertFalse($report->isHealthy());
        self::assertSame('throwing', $results[1]->name);
        self::assertFalse($results[1]->healthy);
        self::assertSame('check failed', $results[1]->detail);
        self::assertSame(['in-memory' => 'ok', 'throwing' => 'error'], $report->toArray()['checks']);
    }
}
