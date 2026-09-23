<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

final class EventCarbonFootprintReportCompensation
{
    /**
     * @param list<EventCarbonFootprintReportCompensationProjectLine> $projectLines
     * @param list<EventCarbonFootprintReportCompensationLine>        $traceabilityLines
     */
    public function __construct(
        public readonly float $ownTonnes,
        public readonly float $attendeesTonnes,
        public readonly float $totalTonnes,
        public readonly float $coveragePercentage,
        public readonly int $ownContributions,
        public readonly int $attendeesContributions,
        public readonly \DateTimeImmutable $cutOffAt,
        public readonly ?\DateTimeImmutable $lastCompensatedAt,
        public readonly array $projectLines = [],
        public readonly array $traceabilityLines = [],
    ) {}

    public function hasCompensations(): bool
    {
        return $this->ownContributions > 0 || $this->attendeesContributions > 0;
    }
}
