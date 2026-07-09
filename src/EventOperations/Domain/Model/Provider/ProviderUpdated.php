<?php

declare(strict_types=1);

namespace App\EventOperations\Domain\Model\Provider;

use App\EventOperations\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\Address;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class ProviderUpdated extends DomainEvent
{
    protected static string $eventName = 'providerUpdated';

    public function __construct(
        private ProviderId $providerId,
        private string $name,
        private string $vatNumber,
        private Address $address,
        private Contact $contact,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function providerId(): ProviderId
    {
        return $this->providerId;
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

    public function contact(): Contact
    {
        return $this->contact;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->providerId();
    }

    public function payload(): array
    {
        return [
            'providerId' => (string) $this->providerId,
            'name' => $this->name,
            'vatNumber' => $this->vatNumber,
            'street' => $this->address->street(),
            'postalCode' => $this->address->postalCode(),
            'city' => $this->address->city(),
            'country' => $this->address->country(),
            'email' => $this->contact->email(),
            'preferredLocale' => $this->contact->preferredLocale()?->value,
            'contactName' => $this->contact->name(),
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

        $contact = new Contact(
            email: $payload['email'] ?? '',
            preferredLocale: isset($payload['preferredLocale']) ? Locale::tryFrom($payload['preferredLocale']) : null,
            name: $payload['contactName'] ?? null,
        );

        return new ProviderUpdated(
            providerId: new ProviderId($payload['providerId']),
            name: $payload['name'],
            vatNumber: $payload['vatNumber'],
            address: $address,
            contact: $contact,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
