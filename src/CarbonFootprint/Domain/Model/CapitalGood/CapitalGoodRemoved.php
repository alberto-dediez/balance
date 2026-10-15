<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CapitalGood;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CapitalGoodRemoved extends DomainEvent
{
    protected static string $eventName = 'capitalGoodRemoved';

    public function __construct(
        private CapitalGoodId $capitalGoodId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function capitalGoodId(): CapitalGoodId
    {
        return $this->capitalGoodId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->capitalGoodId();
    }

    public function payload(): array
    {
        return [
            'capitalGoodId' => (string) $this->capitalGoodId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CapitalGoodRemoved(
            capitalGoodId: new CapitalGoodId($payload['capitalGoodId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
