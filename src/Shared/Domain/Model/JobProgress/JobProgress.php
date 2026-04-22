<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model\JobProgress;

use App\Shared\Domain\Event\JobProgress\JobProgressAdvanced;
use App\Shared\Domain\Event\JobProgress\JobProgressCompleted;
use App\Shared\Domain\Event\JobProgress\JobProgressFailed;
use App\Shared\Domain\Model\AggregateRoot;

class JobProgress
{
    use AggregateRoot;

    /** @param array<\App\Shared\Domain\Event\DomainEvent> $events */
    public function __construct(
        private JobProgressId $id,
        private JobProgressKey $key,
        private int $total,
        private int $current = 0,
        private string $currentStep = '',
        private JobProgressStatus $status = JobProgressStatus::InProgress,
        array $events = [],
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable('NOW'),
        private \DateTimeImmutable $updatedAt = new \DateTimeImmutable('NOW'),
    ) {
        $this->events = $events;
    }

    public static function start(JobProgressKey $key, int $total): self
    {
        return new self(
            id: JobProgressId::generate(),
            key: $key,
            total: $total,
        );
    }

    public function advance(string $step): void
    {
        $this->recordApplyAndPublish(new JobProgressAdvanced(
            jobProgressId: $this->id,
            key: $this->key,
            current: $this->current + 1,
            total: $this->total,
            step: $step,
        ));
    }

    public function complete(): void
    {
        $this->recordApplyAndPublish(new JobProgressCompleted(
            jobProgressId: $this->id,
            key: $this->key,
        ));
    }

    public function fail(): void
    {
        $this->recordApplyAndPublish(new JobProgressFailed(
            jobProgressId: $this->id,
            key: $this->key,
        ));
    }

    public function reset(): void
    {
        $this->current = 0;
        $this->currentStep = '';
        $this->status = JobProgressStatus::InProgress;
        $this->updatedAt = new \DateTimeImmutable('NOW');
    }

    public function id(): JobProgressId
    {
        return $this->id;
    }

    public function key(): JobProgressKey
    {
        return $this->key;
    }

    public function resize(int $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function current(): int
    {
        return $this->current;
    }

    public function currentStep(): string
    {
        return $this->currentStep;
    }

    public function status(): JobProgressStatus
    {
        return $this->status;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function percentage(): float
    {
        if (0 === $this->total) {
            return 0.0;
        }

        return round(($this->current / $this->total) * 100, 1);
    }

    protected function applyJobProgressAdvanced(JobProgressAdvanced $event): void
    {
        $this->current = $event->current();
        $this->currentStep = $event->step();
        $this->updatedAt = $event->occurredOn();
    }

    protected function applyJobProgressCompleted(JobProgressCompleted $event): void
    {
        $this->status = JobProgressStatus::Completed;
        $this->current = $this->total;
        $this->updatedAt = $event->occurredOn();
    }

    protected function applyJobProgressFailed(JobProgressFailed $event): void
    {
        $this->status = JobProgressStatus::Failed;
        $this->updatedAt = $event->occurredOn();
    }
}
