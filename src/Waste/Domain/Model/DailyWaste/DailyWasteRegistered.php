<?php

namespace App\Waste\Domain\Model\DailyWaste;

use DateTimeImmutable;
use App\Waste\Domain\DomainEvent;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Waste\Domain\Model\Container\Purpose;
use App\Security\Domain\Model\Account\AccountId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Waste\Domain\Model\DailyWaste\DailyWasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

final class DailyWasteRegistered extends DomainEvent
{
    protected static string $eventName = 'dailyWasteRegistered';

    public function __construct(
        private readonly DailyWasteId $dailyWasteId,
        private readonly PropertyId $propertyId,
        private readonly WasteId $wasteId,
        private readonly Purpose $purpose,
        private readonly WasteManagerId $wasteManagerId,
        private readonly AccountId $accountId,
        private readonly \DateTimeImmutable $day,
        private readonly float $load = 0,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function dailyWasteId(): DailyWasteId
    {
        return $this->dailyWasteId;
    }

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
    }

    public function wasteId(): WasteId
    {
        return $this->wasteId;
    }

    public function purpose(): Purpose
    {
        return $this->purpose;
    }

    public function wasteManagerId(): WasteManagerId
    {
        return $this->wasteManagerId;
    }

    public function load(): float
    {
        return $this->load;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function day(): \DateTimeImmutable
    {
        return $this->day;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->dailyWasteId;
    }

    public function payload(): array
    {
        return [
            'dailyWasteId' => (string) $this->dailyWasteId,
            'propertyId' => (string) $this->propertyId,
            'waste' => (string) $this->wasteId,
            'purpose' => $this->purpose->value,
            'wasteManagerId' => (string) $this->wasteManagerId,
            'load' => $this->load,
            'accountId' => (string) $this->accountId,
            'day' => $this->day->format('Y-m-d'),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        array $payload = []
    ): static {
        return new DailyWasteRegistered(
            dailyWasteId: new DailyWasteId($payload['dailyWasteId']),
            propertyId: new PropertyId($payload['propertyId']),
            wasteId: new WasteId($payload['waste']),
            purpose: Purpose::tryFrom($payload['purpose']),
            wasteManagerId: isset($payload['wasteManagerId']) ? new WasteManagerId($payload['wasteManagerId']) : WasteManagerId::generate(),
            accountId: new AccountId($payload['accountId']),
            day: new \DateTimeImmutable($payload['day']),
            load: $payload['load'],
            id: $id,
            occurredOn: $occurredOn
        );
    }
}
