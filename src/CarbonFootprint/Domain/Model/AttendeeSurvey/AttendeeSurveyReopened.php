<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurvey;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class AttendeeSurveyReopened extends DomainEvent
{
    protected static string $eventName = 'attendeeSurveyReopened';

    public function __construct(
        private AttendeeSurveyId $attendeeSurveyId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function attendeeSurveyId(): AttendeeSurveyId
    {
        return $this->attendeeSurveyId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->attendeeSurveyId();
    }

    public function payload(): array
    {
        return [
            'attendeeSurveyId' => (string) $this->attendeeSurveyId,
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        return new self(
            attendeeSurveyId: new AttendeeSurveyId($payload['attendeeSurveyId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
