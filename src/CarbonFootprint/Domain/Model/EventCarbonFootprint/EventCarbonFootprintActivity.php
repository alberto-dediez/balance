<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\EventOperations\Domain\Model\Provider\ProviderId;

class EventCarbonFootprintActivity
{
    public function __construct(
        private EventCarbonFootprintActivityId $id,
        private EventCarbonFootprint $eventCarbonFootprint,
        private ActivityId $activityId,
        private Phase $phase,
        private Source $source,
        private ?ProviderId $providerId = null,
        private \DateTimeImmutable $addedAt = new \DateTimeImmutable('NOW'),
    ) {}

    public function id(): EventCarbonFootprintActivityId
    {
        return $this->id;
    }

    public function eventCarbonFootprint(): EventCarbonFootprint
    {
        return $this->eventCarbonFootprint;
    }

    public function activityId(): ActivityId
    {
        return $this->activityId;
    }

    public function phase(): Phase
    {
        return $this->phase;
    }

    public function source(): Source
    {
        return $this->source;
    }

    public function providerId(): ?ProviderId
    {
        return $this->providerId;
    }

    public function addedAt(): \DateTimeImmutable
    {
        return $this->addedAt;
    }
}
