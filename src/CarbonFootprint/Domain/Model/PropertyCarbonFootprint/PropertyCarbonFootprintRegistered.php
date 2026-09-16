<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PropertyCarbonFootprintRegistered extends DomainEvent
{
    protected static string $eventName = 'propertyCarbonFootprintRegistered';

    public function __construct(
        private PropertyCarbonFootprintId $propertyCarbonFootprintId,
        private PropertyId $propertyId,
        private AccountId $accountId,
        private EmissionFactorGroupId $emissionFactorGroupId,
        private int $year,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function propertyCarbonFootprintId(): PropertyCarbonFootprintId
    {
        return $this->propertyCarbonFootprintId;
    }

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function emissionFactorGroupId(): EmissionFactorGroupId
    {
        return $this->emissionFactorGroupId;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->propertyCarbonFootprintId;
    }

    public function payload(): array
    {
        return [
            'propertyCarbonFootprintId' => (string) $this->propertyCarbonFootprintId,
            'propertyId' => (string) $this->propertyId,
            'accountId' => (string) $this->accountId,
            'emissionFactorGroupId' => (string) $this->emissionFactorGroupId,
            'year' => $this->year,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            propertyCarbonFootprintId: new PropertyCarbonFootprintId($payload['propertyCarbonFootprintId']),
            propertyId: new PropertyId($payload['propertyId']),
            accountId: new AccountId($payload['accountId']),
            emissionFactorGroupId: new EmissionFactorGroupId($payload['emissionFactorGroupId']),
            year: (int) $payload['year'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
