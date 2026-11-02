<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\SectorBenchmark;

final readonly class SectorBenchmarkStatistics
{
    public const int MIN_SAMPLE_SIZE = 10;
    public const int MIN_ACCOUNT_COUNT = 5;
    public const float MAX_ACCOUNT_SHARE = 0.4;

    public function __construct(
        public int $sampleSize,
        public int $accountCount,
        public float $median,
        public float $percentile25,
        public float $percentile75,
        public float $mean,
    ) {}

    /** @param list<SectorBenchmarkSample> $samples */
    public static function from(
        array $samples,
        int $minSample = self::MIN_SAMPLE_SIZE,
        int $minAccounts = self::MIN_ACCOUNT_COUNT,
        float $maxAccountShare = self::MAX_ACCOUNT_SHARE,
    ): ?self {
        $sampleSize = count($samples);
        $samplesByAccount = array_count_values(array_map(static fn(SectorBenchmarkSample $sample): string => $sample->accountId, $samples));
        $accountCount = count($samplesByAccount);

        if ([] === $samplesByAccount || $sampleSize < $minSample || $accountCount < $minAccounts) {
            return null;
        }

        if (max($samplesByAccount) > $sampleSize * $maxAccountShare) {
            return null;
        }

        $values = array_map(static fn(SectorBenchmarkSample $sample): float => $sample->value, $samples);
        sort($values);

        return new self(
            sampleSize: $sampleSize,
            accountCount: $accountCount,
            median: self::percentile($values, 0.5),
            percentile25: self::percentile($values, 0.25),
            percentile75: self::percentile($values, 0.75),
            mean: array_sum($values) / $sampleSize,
        );
    }

    /** @param list<float> $sortedValues */
    private static function percentile(array $sortedValues, float $fraction): float
    {
        $position = (count($sortedValues) - 1) * $fraction;
        $lower = (int) floor($position);
        $upper = (int) ceil($position);

        return $sortedValues[$lower] + ($sortedValues[$upper] - $sortedValues[$lower]) * ($position - $lower);
    }
}
