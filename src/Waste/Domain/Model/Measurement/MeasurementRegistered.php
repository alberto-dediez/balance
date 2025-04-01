<?php

namespace App\Waste\Domain\Model\Measurement;

use DateTimeImmutable;
use App\Waste\Domain\DomainEvent;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Container\MeasurementMethod;

final class MeasurementRegistered extends DomainEvent
{
    protected static string $eventName = 'measurementRegistered';

    public function __construct(
        private MeasurementId $measurementId, 
        private ContainerId $containerId,
        private PropertyId $propertyId,
        private WasteId $wasteId,
        private MeasurementMethod $measurementMethod,
        private Purpose $purpose,
        private UserId $registeredBy,
        private AccountId $accountId,
        private int $quantity = 0,
        private float $load = 0,
        private ?string $justification = null,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function measurementId(): MeasurementId
    {
        return $this->measurementId;
    }

    public function containerId(): ContainerId
    {
        return $this->containerId;
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

    public function measurementMethod(): MeasurementMethod
    {
        return $this->measurementMethod;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function load(): float
    {
        return $this->load;
    }

    public function justification(): ?string
    {
        return $this->justification;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function registeredBy(): UserId
    {
        return $this->registeredBy;
    }

    public function payload(): ?array
    {
        return [
            'measurementId' => (string) $this->measurementId,
            'containerId' => (string) $this->containerId,
            'propertyId' => (string) $this->propertyId,
            'waste' => (string) $this->wasteId,
            'purpose' => $this->purpose->value,
            'measurementMethod' => $this->measurementMethod->value,
            'quantity' => $this->quantity,
            'load' => $this->load,
            'justification' => $this->justification,
            'accountId' => (string) $this->accountId,
            'registeredBy' => (string) $this->registeredBy
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new MeasurementRegistered(
            measurementId: new MeasurementId($payload['measurementId']), 
            containerId: new ContainerId($payload['containerId']),
            propertyId: new PropertyId($payload['propertyId']),
            wasteId: new WasteId($payload['waste']), 
            purpose: Purpose::tryFrom($payload['purpose']),
            measurementMethod: MeasurementMethod::tryFrom($payload['measurementMethod']),
            quantity: $payload['quantity'],
            load: $payload['load'],
            justification: $payload['justification'],
            registeredBy: new UserId($payload['registeredBy']),
            accountId: new AccountId($payload['accountId']),
            id: $id,
            occurredOn: $occurredOn
        );
    }
}