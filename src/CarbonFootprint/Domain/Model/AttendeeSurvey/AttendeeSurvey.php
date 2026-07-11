<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurvey;

use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class AttendeeSurvey implements \Stringable
{
    use AggregateRoot;

    /** @param array<DomainEvent> $events */
    public function __construct(
        private AttendeeSurveyId $id,
        private ?EventId $eventId = null,
        private ?AccountId $accountId = null,
        #[\SensitiveParameter]
        private string $token = '',
        private AttendeeSurveyStatus $status = AttendeeSurveyStatus::Open,
        private ?int $realAttendees = null,
        private ?ConfidenceLevel $confidenceLevel = null,
        private ?float $marginOfError = null,
        private ?\DateTimeImmutable $closedAt = null,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): AttendeeSurveyId
    {
        return $this->id;
    }

    public function eventId(): ?EventId
    {
        return $this->eventId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function status(): AttendeeSurveyStatus
    {
        return $this->status;
    }

    public function realAttendees(): ?int
    {
        return $this->realAttendees;
    }

    public function confidenceLevel(): ?ConfidenceLevel
    {
        return $this->confidenceLevel;
    }

    public function marginOfError(): ?float
    {
        return $this->marginOfError;
    }

    public function closedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function register(
        EventId $eventId,
        AccountId $accountId,
        #[\SensitiveParameter]
        string $token,
    ): void {
        $this->recordApplyAndPublish(
            new AttendeeSurveyRegistered(
                attendeeSurveyId: $this->id(),
                eventId: $eventId,
                accountId: $accountId,
                token: $token,
            ),
        );
    }

    public function close(int $realAttendees, ConfidenceLevel $confidenceLevel, float $marginOfError): void
    {
        $this->recordApplyAndPublish(
            new AttendeeSurveyClosed(
                attendeeSurveyId: $this->id(),
                realAttendees: $realAttendees,
                confidenceLevel: $confidenceLevel,
                marginOfError: $marginOfError,
            ),
        );
    }

    public function applyAttendeeSurveyRegistered(AttendeeSurveyRegistered $event): void
    {
        $this->eventId = $event->eventId();
        $this->accountId = $event->accountId();
        $this->token = $event->token();
        $this->status = AttendeeSurveyStatus::Open;
        $this->registeredAt = $event->occurredOn();
    }

    public function applyAttendeeSurveyClosed(AttendeeSurveyClosed $event): void
    {
        $this->status = AttendeeSurveyStatus::Closed;
        $this->realAttendees = $event->realAttendees();
        $this->confidenceLevel = $event->confidenceLevel();
        $this->marginOfError = $event->marginOfError();
        $this->closedAt = $event->occurredOn();
        $this->updatedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
