<?php

declare(strict_types=1);

namespace App\Library\Domain\Model\Backup;

use App\Library\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MediaBackupFailed extends DomainEvent
{
    protected static string $eventName = 'mediaBackupFailed';

    public function __construct(
        private readonly int $failed,
        private readonly string $error,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function failed(): int
    {
        return $this->failed;
    }

    public function error(): string
    {
        return $this->error;
    }

    public function aggregateRootId(): ?AggregateRootId
    {
        return null;
    }

    /**
     * @return array{failed: int, error: string}
     */
    public function payload(): array
    {
        return [
            'failed' => $this->failed,
            'error' => $this->error,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MediaBackupFailed(
            failed: $payload['failed'] ?? 0,
            error: $payload['error'] ?? '',
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
