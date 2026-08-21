<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculationLink;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class TripCalculationLinkScopeUpdated extends DomainEvent
{
    protected static string $eventName = 'tripCalculationLinkScopeUpdated';

    public function __construct(
        private TripCalculationLinkId $tripCalculationLinkId,
        private TripCalculationScope $scope,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function tripCalculationLinkId(): TripCalculationLinkId
    {
        return $this->tripCalculationLinkId;
    }

    public function scope(): TripCalculationScope
    {
        return $this->scope;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->tripCalculationLinkId();
    }

    public function payload(): array
    {
        return [
            'tripCalculationLinkId' => (string) $this->tripCalculationLinkId,
            'scope' => $this->scope->value,
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        return new self(
            tripCalculationLinkId: new TripCalculationLinkId($payload['tripCalculationLinkId']),
            scope: TripCalculationScope::from($payload['scope']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
