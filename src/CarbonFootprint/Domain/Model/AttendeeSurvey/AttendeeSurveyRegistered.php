<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurvey;

use App\CarbonFootprint\Domain\DomainEvent;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class AttendeeSurveyRegistered extends DomainEvent
{
    protected static string $eventName = 'attendeeSurveyRegistered';

    public function __construct(
        private AttendeeSurveyId $attendeeSurveyId,
        private EventId $eventId,
        private AccountId $accountId,
        #[\SensitiveParameter]
        private string $token,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function attendeeSurveyId(): AttendeeSurveyId
    {
        return $this->attendeeSurveyId;
    }

    public function eventId(): EventId
    {
        return $this->eventId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->attendeeSurveyId();
    }

    public function payload(): array
    {
        return [
            'attendeeSurveyId' => (string) $this->attendeeSurveyId,
            'eventId' => (string) $this->eventId,
            'accountId' => (string) $this->accountId,
            'token' => $this->token,
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        return new self(
            attendeeSurveyId: new AttendeeSurveyId($payload['attendeeSurveyId']),
            eventId: new EventId($payload['eventId']),
            accountId: new AccountId($payload['accountId']),
            token: $payload['token'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
