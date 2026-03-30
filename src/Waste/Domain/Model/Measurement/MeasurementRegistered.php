<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Measurement;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Waste\Domain\DomainEvent;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Domain\Model\Container\Purpose;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

final class MeasurementRegistered extends DomainEvent
{
    protected static string $eventName = 'measurementRegistered';

    public function __construct(
        private readonly MeasurementId $measurementId,
        private readonly ContainerId $containerId,
        private readonly PropertyId $propertyId,
        private readonly WasteId $wasteId,
        private readonly MeasurementMethod $measurementMethod,
        private readonly Purpose $purpose,
        private readonly WasteManagerId $wasteManagerId,
        private readonly UserId $registeredBy,
        private readonly AccountId $accountId,
        private readonly \DateTimeImmutable $day,
        private readonly int $quantity = 0,
        private readonly float $load = 0,
        private readonly Unit $unit = Unit::Kilogram,
        private readonly ?string $justification = null,
        private readonly ?string $reference = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
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

    public function wasteManagerId(): WasteManagerId
    {
        return $this->wasteManagerId;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function load(): float
    {
        return $this->load;
    }

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function justification(): ?string
    {
        return $this->justification;
    }

    public function reference(): ?string
    {
        return $this->reference;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function registeredBy(): UserId
    {
        return $this->registeredBy;
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
            'containerId' => (string) $this->containerId,
            'propertyId' => (string) $this->propertyId,
            'waste' => (string) $this->wasteId,
            'purpose' => $this->purpose->value,
            'measurementMethod' => $this->measurementMethod->value,
            'wasteManagerId' => (string) $this->wasteManagerId,
            'quantity' => $this->quantity,
            'load' => $this->load,
            'unit' => $this->unit->value,
            'justification' => $this->justification,
            'reference' => $this->reference,
            'accountId' => (string) $this->accountId,
            'registeredBy' => (string) $this->registeredBy,
            'day' => $this->day->format('Y-m-d'),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $measurementMethod = MeasurementMethod::tryFrom($payload['measurementMethod']);
        $guessedUnit = MeasurementMethod::Volume === $measurementMethod ? Unit::Liter : Unit::Kilogram;
        $purpose = Purpose::tryFrom($payload['purpose']);
        $unit = isset($payload['unit']) && !empty($payload['unit']) ? Unit::tryFrom($payload['unit']) : $guessedUnit;
        assert(null !== $measurementMethod);
        assert(null !== $purpose);
        assert(null !== $unit);

        return new MeasurementRegistered(
            measurementId: new MeasurementId($payload['measurementId']),
            containerId: new ContainerId($payload['containerId']),
            propertyId: new PropertyId($payload['propertyId']),
            wasteId: new WasteId($payload['waste']),
            measurementMethod: $measurementMethod,
            purpose: $purpose,
            wasteManagerId: isset($payload['wasteManagerId']) ? new WasteManagerId($payload['wasteManagerId']) : WasteManagerId::generate(),
            registeredBy: new UserId($payload['registeredBy']),
            accountId: new AccountId($payload['accountId']),
            day: isset($payload['day']) ? new \DateTimeImmutable($payload['day']) : $occurredOn,
            quantity: $payload['quantity'],
            load: $payload['load'],
            unit: $unit,
            justification: $payload['justification'],
            reference: $payload['reference'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
