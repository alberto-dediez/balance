<?php

namespace App\Property\Domain\Model\Brand;

use DateTimeImmutable;
use App\Property\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Property\Domain\Model\Brand\BrandId;
use App\Property\Domain\Model\Property\ExternalId;

final class BrandUpdated extends DomainEvent
{
    protected static string $eventName = 'brandUpdated';

    public function __construct(
        private BrandId $brandId, 
        private string $name, 
        private ?ExternalId $externalId = null,
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

    public function payload(): ?array
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
        ?array $payload
    ): static {
        return new BrandUpdated(
            brandId: new BrandId($payload['brandId']), 
            name: $payload['name'],
            externalId: isset($payload['externalId']) ? $payload['externalId'] : null,
            id: $id,
            occurredOn: $occurredOn
        );
    }
}