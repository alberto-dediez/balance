<?php

namespace App\Property\Domain\Model\Property;

use DateTimeImmutable;
use App\Property\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Security\Domain\Model\Account\AccountId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;

final class PropertyUpdated extends DomainEvent
{
    protected static string $eventName = 'propertyUpdated';

    public function __construct(
        private PropertyId $propertyId, 
        private string $name, 
        private ?PropertyInfo $info = null,
        private ?ExternalId $externalId = null,
        private ?AccountId $accountId = null,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function info(): ?PropertyInfo
    {
        return $this->info;
    }

    public function externalId(): ?ExternalId
    {
        return $this->externalId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function payload(): ?array
    {
        return [
            'propertyId' => (string) $this->propertyId,
            'accountId' => null !== $this->accountId ? ((string) $this->accountId) : null,
            'name' => $this->name,
            'info' => $this->info,
            'externalId' => $this->externalId
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new PropertyUpdated(
            propertyId: new PropertyId($payload['propertyId']), 
            accountId: null !== $payload['accountId'] ? new AccountId($payload['accountId']) : null, 
            name: $payload['name'],
            info: isset($payload['info']) ? $payload['info'] : null,
            externalId: isset($payload['externalId']) ? $payload['externalId'] : null,
            id: $id,
            occurredOn: $occurredOn
        );
    }
}