<?php

namespace App\Waste\Domain\Model\Container;

use DateTimeImmutable;
use App\Waste\Domain\DomainEvent;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Shared\Domain\Event\DomainEventId;
use App\Waste\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

final class ContainerRegistered extends DomainEvent
{
    protected static string $eventName = 'containerRegistered';

    public function __construct(
        private ContainerId $containerId, 
        private AccountId $accountId,
        private PropertyId $propertyId,
        private WasteId $wasteId,
        private MeasurementMethod $measurementMethod,
        private string $volume,
        private Purpose $purpose,
        private WasteManagerId $wasteManagerId,
        private QrCode $qrCode,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function containerId(): ContainerId
    {
        return $this->containerId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
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

    public function volume(): string
    {
        return $this->volume;
    }

    public function wasteManagerId(): WasteManagerId
    {
        return $this->wasteManagerId;
    }

    public function qrCode(): QrCode
    {
        return $this->qrCode;
    }
    
    public function payload(): ?array
    {
        return [
            'containerId' => (string) $this->containerId,
            'accountId' => (string) $this->accountId,
            'propertyId' => (string) $this->propertyId,
            'waste' => (string) $this->wasteId,
            'purpose' => $this->purpose->value,
            'measurementMethod' => $this->measurementMethod->value,
            'wasteManagerId' => (string) $this->wasteManagerId,
            'volume' => $this->volume,
            'qrCode' => $this->qrCode->path()
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new ContainerRegistered(
            containerId: new ContainerId($payload['containerId']), 
            accountId: new AccountId($payload['accountId']),
            propertyId: new PropertyId($payload['propertyId']),
            wasteId: new WasteId($payload['waste']), 
            purpose: Purpose::tryFrom($payload['purpose']),
            measurementMethod: MeasurementMethod::tryFrom($payload['measurementMethod']),
            volume: isset($payload['volume']) ? $payload['volume'] : '0',
            wasteManagerId: isset($payload['wasteManagerId']) ? new WasteManagerId($payload['wasteManagerId']) : WasteManagerId::generate(),
            qrCode: new QrCode(isset($payload['qrCode']) ? $payload['qrCode'] : ''),
            id: $id,
            occurredOn: $occurredOn
        );
    }
}