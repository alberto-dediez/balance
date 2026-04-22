<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Company;

use App\Property\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CompanyUpdated extends DomainEvent
{
    protected static string $eventName = 'companyUpdated';

    public function __construct(
        private CompanyId $companyId,
        private string $name,
        private string $vatNumber,
        private ?CompanyAddress $address,
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

    public function address(): ?CompanyAddress
    {
        return $this->address;
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
            'street' => $this->address?->street(),
            'postalCode' => $this->address?->postalCode(),
            'city' => $this->address?->city(),
            'country' => $this->address?->country(),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $address = new CompanyAddress(
            street: $payload['street'] ?? null,
            postalCode: $payload['postalCode'] ?? null,
            city: $payload['city'] ?? null,
            country: $payload['country'] ?? null,
        );

        return new CompanyUpdated(
            companyId: new CompanyId($payload['companyId']),
            name: $payload['name'],
            vatNumber: $payload['vatNumber'],
            address: $address,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
