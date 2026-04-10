<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\TrainingSession;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class TrainingSession implements \Stringable
{
    use AggregateRoot;

    /** @param array<DomainEvent> $events */
    public function __construct(
        private TrainingSessionId $id,
        private ?AccountId $accountId = null,
        private \DateTimeImmutable $sessionDate = new \DateTimeImmutable('NOW'),
        private string $title = '',
        private string $topic = '',
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): TrainingSessionId
    {
        return $this->id;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
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

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function register(
        \DateTimeImmutable $sessionDate,
        string $title,
        string $topic,
        AccountId $accountId,
    ): void {
        $this->recordApplyAndPublish(
            new TrainingSessionRegistered(
                trainingSessionId: $this->id(),
                sessionDate: $sessionDate,
                title: $title,
                topic: $topic,
                accountId: $accountId,
            ),
        );
    }

    public function update(
        \DateTimeImmutable $sessionDate,
        string $title,
        string $topic,
    ): void {
        $this->recordApplyAndPublish(
            new TrainingSessionUpdated(
                trainingSessionId: $this->id(),
                sessionDate: $sessionDate,
                title: $title,
                topic: $topic,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new TrainingSessionRemoved(
                trainingSessionId: $this->id(),
            ),
        );
    }

    public function applyTrainingSessionRegistered(TrainingSessionRegistered $event): void
    {
        $this->sessionDate = $event->sessionDate();
        $this->title = $event->title();
        $this->topic = $event->topic();
        $this->accountId = $event->accountId();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyTrainingSessionUpdated(TrainingSessionUpdated $event): void
    {
        $this->sessionDate = $event->sessionDate();
        $this->title = $event->title();
        $this->topic = $event->topic();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyTrainingSessionRemoved(TrainingSessionRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
