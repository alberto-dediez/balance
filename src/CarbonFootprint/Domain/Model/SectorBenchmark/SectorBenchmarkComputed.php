<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\SectorBenchmark;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class SectorBenchmarkComputed extends DomainEvent
{
    protected static string $eventName = 'sectorBenchmarkComputed';

    public function __construct(
        private SectorBenchmarkId $sectorBenchmarkId,
        private CarbonFootprintReportSubjectType $subjectType,
        private string $segmentKey,
        private int $year,
        private IntensityIndicator $indicator,
        private int $sampleSize,
        private int $accountCount,
        private string $median,
        private string $percentile25,
        private string $percentile75,
        private string $mean,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function sectorBenchmarkId(): SectorBenchmarkId
    {
        return $this->sectorBenchmarkId;
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

    public function aggregateRootId(): AggregateRootId
    {
        return $this->sectorBenchmarkId;
    }

    public function payload(): array
    {
        return [
            'sectorBenchmarkId' => (string) $this->sectorBenchmarkId,
            'subjectType' => $this->subjectType->value,
            'segmentKey' => $this->segmentKey,
            'year' => $this->year,
            'indicator' => $this->indicator->value,
            'sampleSize' => $this->sampleSize,
            'accountCount' => $this->accountCount,
            'median' => $this->median,
            'percentile25' => $this->percentile25,
            'percentile75' => $this->percentile75,
            'mean' => $this->mean,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            sectorBenchmarkId: new SectorBenchmarkId($payload['sectorBenchmarkId']),
            subjectType: CarbonFootprintReportSubjectType::from($payload['subjectType']),
            segmentKey: $payload['segmentKey'],
            year: (int) $payload['year'],
            indicator: IntensityIndicator::from($payload['indicator']),
            sampleSize: (int) $payload['sampleSize'],
            accountCount: (int) $payload['accountCount'],
            median: $payload['median'],
            percentile25: $payload['percentile25'],
            percentile75: $payload['percentile75'],
            mean: $payload['mean'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
