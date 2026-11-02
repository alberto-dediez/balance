<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityDenominators;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CompanyCarbonFootprintIntensityDenominatorsDeclared extends DomainEvent
{
    protected static string $eventName = 'companyCarbonFootprintIntensityDenominatorsDeclared';

    public function __construct(
        private CompanyCarbonFootprintId $companyCarbonFootprintId,
        private IntensityDenominators $denominators,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function companyCarbonFootprintId(): CompanyCarbonFootprintId
    {
        return $this->companyCarbonFootprintId;
    }

    public function denominators(): IntensityDenominators
    {
        return $this->denominators;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->companyCarbonFootprintId;
    }

    public function payload(): array
    {
        return [
            'companyCarbonFootprintId' => (string) $this->companyCarbonFootprintId,
            'occupiedRooms' => $this->denominators->occupiedRooms(),
            'stays' => $this->denominators->stays(),
            'builtAreaM2' => $this->denominators->builtAreaM2(),
            'coversServed' => $this->denominators->coversServed(),
            'averageHeadcount' => $this->denominators->averageHeadcount(),
            'revenueEur' => $this->denominators->revenueEur(),
            'customersServed' => $this->denominators->customersServed(),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            companyCarbonFootprintId: new CompanyCarbonFootprintId($payload['companyCarbonFootprintId']),
            denominators: new IntensityDenominators(
                occupiedRooms: $payload['occupiedRooms'] ?? null,
                stays: $payload['stays'] ?? null,
                builtAreaM2: $payload['builtAreaM2'] ?? null,
                coversServed: $payload['coversServed'] ?? null,
                averageHeadcount: $payload['averageHeadcount'] ?? null,
                revenueEur: $payload['revenueEur'] ?? null,
                customersServed: $payload['customersServed'] ?? null,
            ),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
