<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\DailyWaste;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Waste\Domain\DomainEvent;

final class DailyWasteUpdated extends DomainEvent
{
    protected static string $eventName = 'dailyWasteUpdated';

    public function __construct(
        private readonly DailyWasteId $dailyWasteId,
        private readonly AccountId $accountId,
        private readonly \DateTimeImmutable $day,
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

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function day(): \DateTimeImmutable
    {
        return $this->day;
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
            'accountId' => (string) $this->accountId,
            'day' => $this->day->format('Y-m-d'),
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
            accountId: new AccountId($payload['accountId']),
            day: new \DateTimeImmutable($payload['day']),
            load: $payload['load'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
