<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\CalculationStage;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SourceExclusion;

class EventCarbonFootprintExclusion implements SourceExclusion
{
    public function __construct(
        private EventCarbonFootprintExclusionId $id,
        private EventCarbonFootprint $eventCarbonFootprint,
        private Phase $phase,
        private Source $source,
        private ExclusionReason $reason,
        private ?string $comment = null,
        private \DateTimeImmutable $excludedAt = new \DateTimeImmutable('NOW'),
    ) {}

    public function id(): EventCarbonFootprintExclusionId
    {
        return $this->id;
    }

    public function eventCarbonFootprint(): EventCarbonFootprint
    {
        return $this->eventCarbonFootprint;
    }

    public function phase(): Phase
    {
        return $this->phase;
    }

    public function stage(): CalculationStage
    {
        return $this->phase;
    }

    public function source(): Source
    {
        return $this->source;
    }

    public function reason(): ExclusionReason
    {
        return $this->reason;
    }

    public function comment(): ?string
    {
        return $this->comment;
    }

    public function excludedAt(): \DateTimeImmutable
    {
        return $this->excludedAt;
    }

    public function update(ExclusionReason $reason, ?string $comment, \DateTimeImmutable $excludedAt): void
    {
        $this->reason = $reason;
        $this->comment = $comment;
        $this->excludedAt = $excludedAt;
    }
}
