<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\DailyWaste;

use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Waste\Domain\DomainEvent;

final class DailyWasteUpdated extends DomainEvent
{
    protected static string $eventName = 'dailyWasteUpdated';

    public function __construct(
        private readonly DailyWasteId $dailyWasteId,
        private readonly float $load = 0,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function dailyWasteId(): DailyWasteId
    {
        return $this->dailyWasteId;
    }

    public function load(): float
    {
        return $this->load;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->dailyWasteId;
    }

    public function payload(): array
    {
        return [
            'dailyWasteId' => (string) $this->dailyWasteId,
            'load' => $this->load,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new DailyWasteUpdated(
            dailyWasteId: new DailyWasteId($payload['dailyWasteId']),
            load: $payload['load'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
