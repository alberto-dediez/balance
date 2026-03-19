<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Measurement;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Waste\Domain\DomainEvent;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

final class MeasurementRemoved extends DomainEvent
{
    protected static string $eventName = 'measurementRemoved';

    public function __construct(
        private readonly MeasurementId $measurementId,
        private readonly PropertyId $propertyId,
        private readonly WasteId $wasteId,
        private readonly Purpose $purpose,
        private readonly WasteManagerId $wasteManagerId,
        private readonly AccountId $accountId,
        private readonly \DateTimeImmutable $day,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function measurementId(): MeasurementId
    {
        return $this->measurementId;
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
        return $this->measurementId;
    }

    public function payload(): array
    {
        return [
            'measurementId' => (string) $this->measurementId,
            'propertyId' => (string) $this->propertyId,
            'waste' => (string) $this->wasteId,
            'purpose' => $this->purpose->value,
            'wasteManagerId' => (string) $this->wasteManagerId,
            'accountId' => (string) $this->accountId,
            'day' => $this->day->format('Y-m-d'),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MeasurementRemoved(
            measurementId: new MeasurementId($payload['measurementId']),
            propertyId: new PropertyId($payload['propertyId']),
            wasteId: new WasteId($payload['waste']),
            purpose: Purpose::tryFrom($payload['purpose']),
            wasteManagerId: isset($payload['wasteManagerId']) ? new WasteManagerId($payload['wasteManagerId']) : WasteManagerId::generate(),
            accountId: new AccountId($payload['accountId']),
            day: new \DateTimeImmutable($payload['day']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
