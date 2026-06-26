<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CateringItem;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CateringItemEmissionFactorRemoved extends DomainEvent
{
    protected static string $eventName = 'cateringItemEmissionFactorRemoved';

    public function __construct(
        private CateringItemId $cateringItemId,
        private int $year,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function cateringItemId(): CateringItemId
    {
        return $this->cateringItemId;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->cateringItemId();
    }

    public function payload(): array
    {
        return [
            'cateringItemId' => (string) $this->cateringItemId,
            'year' => $this->year,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CateringItemEmissionFactorRemoved(
            cateringItemId: new CateringItemId($payload['cateringItemId']),
            year: $payload['year'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
