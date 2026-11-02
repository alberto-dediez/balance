<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\SectorBenchmark;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use App\Shared\Domain\Model\AggregateRoot;

class SectorBenchmark implements \Stringable
{
    use AggregateRoot;

    /** @param array<\App\Shared\Domain\Event\DomainEvent> $events */
    public function __construct(
        private SectorBenchmarkId $id,
        private CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property,
        private string $segmentKey = '',
        private int $year = 0,
        private IntensityIndicator $indicator = IntensityIndicator::PerStay,
        private int $sampleSize = 0,
        private int $accountCount = 0,
        private string $median = '0',
        private string $percentile25 = '0',
        private string $percentile75 = '0',
        private string $mean = '0',
        array $events = [],
        private \DateTimeImmutable $computedAt = new \DateTimeImmutable('NOW'),
    ) {
        $this->events = $events;
    }

    public static function compute(
        SectorBenchmarkId $id,
        CarbonFootprintReportSubjectType $subjectType,
        string $segmentKey,
        int $year,
        IntensityIndicator $indicator,
        SectorBenchmarkStatistics $statistics,
    ): self {
        $benchmark = new self(id: $id);

        $benchmark->recordApplyAndPublish(
            new SectorBenchmarkComputed(
                sectorBenchmarkId: $id,
                subjectType: $subjectType,
                segmentKey: $segmentKey,
                year: $year,
                indicator: $indicator,
                sampleSize: $statistics->sampleSize,
                accountCount: $statistics->accountCount,
                median: self::decimal($statistics->median),
                percentile25: self::decimal($statistics->percentile25),
                percentile75: self::decimal($statistics->percentile75),
                mean: self::decimal($statistics->mean),
            ),
        );

        return $benchmark;
    }

    public function id(): SectorBenchmarkId
    {
        return $this->id;
    }

    public function subjectType(): CarbonFootprintReportSubjectType
    {
        return $this->subjectType;
    }

    public function segmentKey(): string
    {
        return $this->segmentKey;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function indicator(): IntensityIndicator
    {
        return $this->indicator;
    }

    public function sampleSize(): int
    {
        return $this->sampleSize;
    }

    public function accountCount(): int
    {
        return $this->accountCount;
    }

    public function median(): string
    {
        return $this->median;
    }

    public function percentile25(): string
    {
        return $this->percentile25;
    }

    public function percentile75(): string
    {
        return $this->percentile75;
    }

    public function mean(): string
    {
        return $this->mean;
    }

    public function computedAt(): \DateTimeImmutable
    {
        return $this->computedAt;
    }

    public function isPublishable(): bool
    {
        return $this->sampleSize >= SectorBenchmarkStatistics::MIN_SAMPLE_SIZE
            && $this->accountCount >= SectorBenchmarkStatistics::MIN_ACCOUNT_COUNT;
    }

    public function applySectorBenchmarkComputed(SectorBenchmarkComputed $event): void
    {
        $this->subjectType = $event->subjectType();
        $this->segmentKey = $event->segmentKey();
        $this->year = $event->year();
        $this->indicator = $event->indicator();
        $this->sampleSize = $event->sampleSize();
        $this->accountCount = $event->accountCount();
        $this->median = $event->median();
        $this->percentile25 = $event->percentile25();
        $this->percentile75 = $event->percentile75();
        $this->mean = $event->mean();
        $this->computedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return sprintf('%s/%s/%d/%s', $this->subjectType->value, $this->segmentKey, $this->year, $this->indicator->value);
    }

    private static function decimal(float $value): string
    {
        return number_format($value, 4, '.', '');
    }
}
