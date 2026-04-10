<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\TrainingSession;

use App\FoodLoss\Domain\DomainEvent;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class TrainingSessionRegistered extends DomainEvent
{
    protected static string $eventName = 'trainingSessionRegistered';

    public function __construct(
        private TrainingSessionId $trainingSessionId,
        private \DateTimeImmutable $sessionDate,
        private string $title,
        private string $topic,
        private AccountId $accountId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function trainingSessionId(): TrainingSessionId
    {
        return $this->trainingSessionId;
    }

    public function sessionDate(): \DateTimeImmutable
    {
        return $this->sessionDate;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function topic(): string
    {
        return $this->topic;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->trainingSessionId();
    }

    public function payload(): array
    {
        return [
            'trainingSessionId' => (string) $this->trainingSessionId,
            'sessionDate' => $this->sessionDate->format(\DateTimeInterface::ATOM),
            'title' => $this->title,
            'topic' => $this->topic,
            'accountId' => (string) $this->accountId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new TrainingSessionRegistered(
            trainingSessionId: new TrainingSessionId($payload['trainingSessionId']),
            sessionDate: new \DateTimeImmutable($payload['sessionDate']),
            title: $payload['title'],
            topic: $payload['topic'],
            accountId: new AccountId($payload['accountId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
