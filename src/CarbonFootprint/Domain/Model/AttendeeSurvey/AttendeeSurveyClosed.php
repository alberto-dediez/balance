<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurvey;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class AttendeeSurveyClosed extends DomainEvent
{
    protected static string $eventName = 'attendeeSurveyClosed';

    public function __construct(
        private AttendeeSurveyId $attendeeSurveyId,
        private int $realAttendees,
        private ConfidenceLevel $confidenceLevel,
        private float $marginOfError,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function attendeeSurveyId(): AttendeeSurveyId
    {
        return $this->attendeeSurveyId;
    }

    public function realAttendees(): int
    {
        return $this->realAttendees;
    }

    public function confidenceLevel(): ConfidenceLevel
    {
        return $this->confidenceLevel;
    }

    public function marginOfError(): float
    {
        return $this->marginOfError;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->attendeeSurveyId();
    }

    public function payload(): array
    {
        return [
            'attendeeSurveyId' => (string) $this->attendeeSurveyId,
            'realAttendees' => $this->realAttendees,
            'confidenceLevel' => $this->confidenceLevel->value,
            'marginOfError' => $this->marginOfError,
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        return new self(
            attendeeSurveyId: new AttendeeSurveyId($payload['attendeeSurveyId']),
            realAttendees: $payload['realAttendees'],
            confidenceLevel: ConfidenceLevel::from($payload['confidenceLevel']),
            marginOfError: $payload['marginOfError'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
