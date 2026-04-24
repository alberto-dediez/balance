<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Company;

use App\Property\Domain\DomainEvent;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\Address;
use App\Shared\Domain\Model\AggregateRootId;

final class CompanyRegistered extends DomainEvent
{
    protected static string $eventName = 'companyRegistered';

    public function __construct(
        private CompanyId $companyId,
        private string $name,
        private string $vatNumber,
        private Address $address,
        private ?AccountId $accountId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function companyId(): CompanyId
    {
        return $this->companyId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function vatNumber(): string
    {
        return $this->vatNumber;
    }

    public function address(): Address
    {
        return $this->address;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->companyId();
    }

    public function payload(): array
    {
        return [
            'companyId' => (string) $this->companyId,
            'name' => $this->name,
            'vatNumber' => $this->vatNumber,
            'street' => $this->address->street(),
            'postalCode' => $this->address->postalCode(),
            'city' => $this->address->city(),
            'country' => $this->address->country(),
            'accountId' => $this->accountId ? (string) $this->accountId : null,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $address = new Address(
            street: $payload['street'] ?? null,
            postalCode: $payload['postalCode'] ?? null,
            city: $payload['city'] ?? null,
            country: $payload['country'] ?? null,
        );

        return new CompanyRegistered(
            companyId: new CompanyId($payload['companyId']),
            name: $payload['name'],
            vatNumber: $payload['vatNumber'],
            address: $address,
            accountId: isset($payload['accountId']) ? new AccountId($payload['accountId']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
