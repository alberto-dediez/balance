<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OvernightStay;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class OvernightStayEmissionFactorRemoved extends DomainEvent
{
    protected static string $eventName = 'overnightStayEmissionFactorRemoved';

    public function __construct(
        private OvernightStayId $overnightStayId,
        private int $year,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function overnightStayId(): OvernightStayId
    {
        return $this->overnightStayId;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->overnightStayId();
    }

    public function payload(): array
    {
        return [
            'overnightStayId' => (string) $this->overnightStayId,
            'year' => $this->year,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new OvernightStayEmissionFactorRemoved(
            overnightStayId: new OvernightStayId($payload['overnightStayId']),
            year: $payload['year'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
