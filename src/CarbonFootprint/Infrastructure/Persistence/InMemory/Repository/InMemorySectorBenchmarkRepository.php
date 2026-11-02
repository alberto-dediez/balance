<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use App\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmark;
use App\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmarkRepository;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
class InMemorySectorBenchmarkRepository implements SectorBenchmarkRepository
{
    /** @param array<string, SectorBenchmark> $benchmarks */
    public function __construct(
        private array $benchmarks = [],
    ) {}

    public function add(SectorBenchmark $benchmark): void
    {
        $this->benchmarks[(string) $benchmark->id()] = $benchmark;
    }

    public function removeAll(): void
    {
        $this->benchmarks = [];
    }

    /** @param list<SectorBenchmark> $benchmarks */
    public function replaceAll(array $benchmarks): void
    {
        $this->removeAll();

        foreach ($benchmarks as $benchmark) {
            $this->add($benchmark);
        }
    }

    public function findBySegment(CarbonFootprintReportSubjectType $subjectType, string $segmentKey, int $year, IntensityIndicator $indicator): ?SectorBenchmark
    {
        foreach ($this->benchmarks as $benchmark) {
            if ($benchmark->subjectType() === $subjectType
                && $benchmark->segmentKey() === $segmentKey
                && $benchmark->year() === $year
                && $benchmark->indicator() === $indicator
            ) {
                return $benchmark;
            }
        }

        return null;
    }

    /** @return list<SectorBenchmark> */
    public function findAll(): array
    {
        return array_values($this->benchmarks);
    }
}
