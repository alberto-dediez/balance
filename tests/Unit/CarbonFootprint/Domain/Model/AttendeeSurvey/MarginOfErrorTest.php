<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\AttendeeSurvey;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\MarginOfError;
use PHPUnit\Framework\TestCase;

class MarginOfErrorTest extends TestCase
{
    /** @return iterable<string, array{ConfidenceLevel, float}> */
    public static function confidenceLevelProvider(): iterable
    {
        yield 'eighty' => [ConfidenceLevel::Eighty, 1.282];
        yield 'eighty five' => [ConfidenceLevel::EightyFive, 1.440];
        yield 'ninety' => [ConfidenceLevel::Ninety, 1.645];
        yield 'ninety five' => [ConfidenceLevel::NinetyFive, 1.960];
        yield 'ninety nine' => [ConfidenceLevel::NinetyNine, 2.576];
    }

    /** @dataProvider confidenceLevelProvider */
    public function testCalculateWithKnownValues(ConfidenceLevel $level, float $zScore): void
    {
        $n = 100;
        $N = 500;

        $expected = $zScore * sqrt(0.25 / $n) * sqrt(($N - $n) / ($N - 1));

        $this->assertEqualsWithDelta($expected, MarginOfError::calculate($n, $N, $level), 0.0000001);
    }

    public function testCalculateReturnsZeroWhenSampleIsZeroOrNegative(): void
    {
        $this->assertSame(0.0, MarginOfError::calculate(0, 100, ConfidenceLevel::Ninety));
        $this->assertSame(0.0, MarginOfError::calculate(-5, 100, ConfidenceLevel::Ninety));
    }

    public function testCalculateReturnsZeroWhenPopulationIsOneOrLess(): void
    {
        $this->assertSame(0.0, MarginOfError::calculate(1, 1, ConfidenceLevel::Ninety));
        $this->assertSame(0.0, MarginOfError::calculate(1, 0, ConfidenceLevel::Ninety));
    }

    public function testCalculateReturnsZeroWhenSampleIsGreaterOrEqualToPopulation(): void
    {
        $this->assertSame(0.0, MarginOfError::calculate(100, 100, ConfidenceLevel::Ninety));
        $this->assertSame(0.0, MarginOfError::calculate(150, 100, ConfidenceLevel::Ninety));
    }

    public function testCalculateKnownValueRegression(): void
    {
        $result = MarginOfError::calculate(100, 200, ConfidenceLevel::NinetyFive);

        $this->assertEqualsWithDelta(0.0693, $result, 0.001);
    }
}
