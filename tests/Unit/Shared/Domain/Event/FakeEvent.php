<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class FakeEvent extends DomainEvent
{
    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [];
    }

    public function aggregateRootId(): ?AggregateRootId
    {
        return null;
    }

    /** @param array<string, mixed> $payload */
    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        return new FakeEvent($id, $occurredOn);
    }
}
