<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

class EventCarbonFootprintExclusion
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
}
