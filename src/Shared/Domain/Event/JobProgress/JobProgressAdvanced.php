<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event\JobProgress;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\JobProgress\JobProgressId;
use App\Shared\Domain\Model\JobProgress\JobProgressKey;

final class JobProgressAdvanced extends DomainEvent
{
    protected static string $eventName = 'jobProgressAdvanced';
    protected static string $context = 'shared';

    public function __construct(
        private readonly JobProgressId $jobProgressId,
        private readonly JobProgressKey $key,
        private readonly int $current,
        private readonly int $total,
        private readonly string $step,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function jobProgressId(): JobProgressId
    {
        return $this->jobProgressId;
    }

    public function key(): JobProgressKey
    {
        return $this->key;
    }

    public function current(): int
    {
        return $this->current;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function step(): string
    {
        return $this->step;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->jobProgressId;
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [
            'jobProgressId' => (string) $this->jobProgressId,
            'key' => (string) $this->key,
            'current' => $this->current,
            'total' => $this->total,
            'step' => $this->step,
        ];
    }

    /** @param array<string, mixed> $payload */
    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        return new self(
            jobProgressId: new JobProgressId($payload['jobProgressId']),
            key: JobProgressKey::fromString($payload['key']),
            current: $payload['current'],
            total: $payload['total'],
            step: $payload['step'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
