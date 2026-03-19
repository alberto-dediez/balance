<?php

namespace App\Property\Domain\Model\Property;

use DateTimeImmutable;
use App\Property\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Property\PropertyInfo;

final class PropertyUnmatched extends DomainEvent
{
    protected static string $eventName = 'propertyUnmatched';

    public function __construct(
        private readonly string $name,
        private readonly ?PropertyInfo $info = null,
        private readonly ?ExternalId $externalId = null,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
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

    public function aggregateRootId(): ?AggregateRootId
    {
        return null;
    }

    public function payload(): array
    {
        return [
            'name' => $this->name,
            'info' => $this->info,
            'externalId' => $this->externalId
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        array $payload = []
    ): static {
        return new PropertyUnmatched(
            name: $payload['name'],
            info: $payload['info'] ?? null,
            externalId: $payload['externalId'] ?? null,
            id: $id,
            occurredOn: $occurredOn
        );
    }
}
