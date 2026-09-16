<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PropertyCarbonFootprintScopeAnswered extends DomainEvent
{
    protected static string $eventName = 'propertyCarbonFootprintScopeAnswered';

    public function __construct(
        private PropertyCarbonFootprintId $propertyCarbonFootprintId,
        private Scope $scope,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function propertyCarbonFootprintId(): PropertyCarbonFootprintId
    {
        return $this->propertyCarbonFootprintId;
    }

    public function scope(): Scope
    {
        return $this->scope;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->propertyCarbonFootprintId;
    }

    public function payload(): array
    {
        return [
            'propertyCarbonFootprintId' => (string) $this->propertyCarbonFootprintId,
            'scope' => $this->scope->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $scope = Scope::tryFrom($payload['scope']);
        assert(null !== $scope);

        return new self(
            propertyCarbonFootprintId: new PropertyCarbonFootprintId($payload['propertyCarbonFootprintId']),
            scope: $scope,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
