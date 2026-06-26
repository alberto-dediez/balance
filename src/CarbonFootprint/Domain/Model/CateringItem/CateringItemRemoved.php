<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CateringItem;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CateringItemRemoved extends DomainEvent
{
    protected static string $eventName = 'cateringItemRemoved';

    public function __construct(
        private CateringItemId $cateringItemId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function cateringItemId(): CateringItemId
    {
        return $this->cateringItemId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->cateringItemId();
    }

    public function payload(): array
    {
        return [
            'cateringItemId' => (string) $this->cateringItemId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CateringItemRemoved(
            cateringItemId: new CateringItemId($payload['cateringItemId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
