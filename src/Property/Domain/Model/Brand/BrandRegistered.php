<?php

namespace App\Property\Domain\Model\Brand;

use DateTimeImmutable;
use App\Property\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Property\Domain\Model\Brand\BrandId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Property\Domain\Model\Property\ExternalId;

final class BrandRegistered extends DomainEvent
{
    protected static string $eventName = 'brandRegistered';

    public function __construct(
        private readonly BrandId $brandId,
        private readonly string $name,
        private readonly ?ExternalId $externalId = null,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function brandId(): BrandId
    {
        return $this->brandId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function externalId(): ?ExternalId
    {
        return $this->externalId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->brandId;
    }

    public function payload(): array
    {
        return [
            'brandId' => (string) $this->brandId,
            'name' => $this->name,
            'externalId' => $this->externalId
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        array $payload = []
    ): static {
        return new BrandRegistered(
            brandId: new BrandId($payload['brandId']),
            name: $payload['name'],
            externalId: $payload['externalId'] ?? null,
            id: $id,
            occurredOn: $occurredOn
        );
    }
}
