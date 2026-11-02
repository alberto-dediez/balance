<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\SectorBenchmark;

use App\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmarkSample;
use App\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmarkStatistics;
use PHPUnit\Framework\TestCase;

final class SectorBenchmarkStatisticsTest extends TestCase
{
    public function testNineSamplesAreNotPublished(): void
    {
        $this->assertNull(SectorBenchmarkStatistics::from($this->samples(['a', 'a', 'b', 'b', 'c', 'c', 'd', 'd', 'e'])));
    }

    public function testTenSamplesOfFourAccountsAreNotPublished(): void
    {
        $this->assertNull(SectorBenchmarkStatistics::from($this->samples(['a', 'a', 'a', 'b', 'b', 'b', 'c', 'c', 'd', 'd'])));
    }

    public function testAnAccountWithMoreThanFortyPercentOfTheSampleIsNotPublished(): void
    {
        $this->assertNull(SectorBenchmarkStatistics::from($this->samples(['a', 'a', 'a', 'a', 'a', 'b', 'b', 'c', 'd', 'e'])));
    }

    public function testAnAccountWithFortyPercentOfTheSampleIsPublished(): void
    {
        $statistics = SectorBenchmarkStatistics::from($this->samples(['a', 'a', 'a', 'a', 'b', 'b', 'b', 'c', 'd', 'e']));

        $this->assertNotNull($statistics);
        $this->assertSame(10, $statistics->sampleSize);
        $this->assertSame(5, $statistics->accountCount);
    }

    public function testComputesMedianPercentilesAndMean(): void
    {
        $statistics = SectorBenchmarkStatistics::from([
            new SectorBenchmarkSample('a', 10.0),
            new SectorBenchmarkSample('b', 2.0),
            new SectorBenchmarkSample('c', 4.0),
            new SectorBenchmarkSample('d', 1.0),
            new SectorBenchmarkSample('e', 3.0),
            new SectorBenchmarkSample('a', 9.0),
            new SectorBenchmarkSample('b', 5.0),
            new SectorBenchmarkSample('c', 7.0),
            new SectorBenchmarkSample('d', 6.0),
            new SectorBenchmarkSample('e', 8.0),
        ]);

        $this->assertNotNull($statistics);
        $this->assertSame(10, $statistics->sampleSize);
        $this->assertSame(5, $statistics->accountCount);
        $this->assertSame(5.5, $statistics->median);
        $this->assertSame(3.25, $statistics->percentile25);
        $this->assertSame(7.75, $statistics->percentile75);
        $this->assertSame(5.5, $statistics->mean);
    }

    /**
     * @param list<string> $accounts
     *
     * @return list<SectorBenchmarkSample>
     */
    private function samples(array $accounts): array
    {
        return array_map(static fn(string $account, int $index): SectorBenchmarkSample => new SectorBenchmarkSample($account, (float) ($index + 1)), $accounts, array_keys($accounts));
    }
}
