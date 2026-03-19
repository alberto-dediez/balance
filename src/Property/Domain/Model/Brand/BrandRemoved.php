<?php

namespace App\Property\Domain\Model\Brand;

use DateTimeImmutable;
use App\Property\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Property\Domain\Model\Brand\BrandId;
use App\Shared\Domain\Model\AggregateRootId;

final class BrandRemoved extends DomainEvent
{
    protected static string $eventName = 'brandRemoved';

    public function __construct(
        private readonly BrandId $brandId,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function brandId(): BrandId
    {
        return $this->brandId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->brandId;
    }

    public function payload(): array
    {
        return [
            'brandId' => (string) $this->brandId
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        array $payload = []
    ): static {
        return new BrandRemoved(
            brandId: new BrandId($payload['brandId']),
            id: $id,
            occurredOn: $occurredOn
        );
    }
}
