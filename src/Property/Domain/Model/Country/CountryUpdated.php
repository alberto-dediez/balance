<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Country;

use App\Property\Domain\DomainEvent;
use App\Property\Domain\Model\Property\ExternalId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CountryUpdated extends DomainEvent
{
    protected static string $eventName = 'countryUpdated';

    public function __construct(
        private readonly CountryId $countryId,
        private readonly string $name,
        private readonly ?string $iso = null,
        private readonly ?ExternalId $externalId = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function countryId(): CountryId
    {
        return $this->countryId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function iso(): ?string
    {
        return $this->iso;
    }

    public function externalId(): ?ExternalId
    {
        return $this->externalId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->countryId;
    }

    public function payload(): array
    {
        return [
            'countryId' => (string) $this->countryId,
            'iso' => $this->iso,
            'name' => $this->name,
            'externalId' => $this->externalId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CountryUpdated(
            countryId: new CountryId($payload['countryId']),
            name: $payload['name'],
            iso: $payload['iso'],
            externalId: $payload['externalId'] ?? null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
