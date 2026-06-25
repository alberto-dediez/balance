<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MerchandisingItem;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MerchandisingItemEmissionFactorRemoved extends DomainEvent
{
    protected static string $eventName = 'merchandisingItemEmissionFactorRemoved';

    public function __construct(
        private MerchandisingItemId $merchandisingItemId,
        private int $year,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function merchandisingItemId(): MerchandisingItemId
    {
        return $this->merchandisingItemId;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->merchandisingItemId();
    }

    public function payload(): array
    {
        return [
            'merchandisingItemId' => (string) $this->merchandisingItemId,
            'year' => $this->year,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MerchandisingItemEmissionFactorRemoved(
            merchandisingItemId: new MerchandisingItemId($payload['merchandisingItemId']),
            year: $payload['year'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
