<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CompanyCarbonFootprintReopened extends DomainEvent
{
    protected static string $eventName = 'companyCarbonFootprintReopened';

    public function __construct(
        private CompanyCarbonFootprintId $companyCarbonFootprintId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function companyCarbonFootprintId(): CompanyCarbonFootprintId
    {
        return $this->companyCarbonFootprintId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->companyCarbonFootprintId;
    }

    public function payload(): array
    {
        return [
            'companyCarbonFootprintId' => (string) $this->companyCarbonFootprintId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            companyCarbonFootprintId: new CompanyCarbonFootprintId($payload['companyCarbonFootprintId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
