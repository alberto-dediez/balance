<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\TrainingSession;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class TrainingSessionUpdated extends DomainEvent
{
    protected static string $eventName = 'trainingSessionUpdated';

    public function __construct(
        private TrainingSessionId $trainingSessionId,
        private \DateTimeImmutable $sessionDate,
        private string $title,
        private string $topic,
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
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new TrainingSessionUpdated(
            trainingSessionId: new TrainingSessionId($payload['trainingSessionId']),
            sessionDate: new \DateTimeImmutable($payload['sessionDate']),
            title: $payload['title'],
            topic: $payload['topic'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
