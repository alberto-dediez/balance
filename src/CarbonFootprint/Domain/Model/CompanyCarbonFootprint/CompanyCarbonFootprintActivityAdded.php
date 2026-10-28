<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CompanyCarbonFootprintActivityAdded extends DomainEvent
{
    protected static string $eventName = 'companyCarbonFootprintActivityAdded';

    public function __construct(
        private CompanyCarbonFootprintId $companyCarbonFootprintId,
        private ActivityId $activityId,
        private Scope $scope,
        private Source $source,
        private ?PropertyId $propertyId = null,
        private ?ProviderId $providerId = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function companyCarbonFootprintId(): CompanyCarbonFootprintId
    {
        return $this->companyCarbonFootprintId;
    }

    public function activityId(): ActivityId
    {
        return $this->activityId;
    }

    public function scope(): Scope
    {
        return $this->scope;
    }

    public function source(): Source
    {
        return $this->source;
    }

    public function propertyId(): ?PropertyId
    {
        return $this->propertyId;
    }

    public function providerId(): ?ProviderId
    {
        return $this->providerId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->companyCarbonFootprintId;
    }

    public function payload(): array
    {
        return [
            'companyCarbonFootprintId' => (string) $this->companyCarbonFootprintId,
            'activityId' => (string) $this->activityId,
            'scope' => $this->scope->value,
            'source' => $this->source->value,
            'propertyId' => $this->propertyId instanceof PropertyId ? (string) $this->propertyId : null,
            'providerId' => $this->providerId instanceof ProviderId ? (string) $this->providerId : null,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $scope = Scope::tryFrom($payload['scope']);
        assert(null !== $scope);

        $source = Source::tryFrom($payload['source']);
        assert(null !== $source);

        return new self(
            companyCarbonFootprintId: new CompanyCarbonFootprintId($payload['companyCarbonFootprintId']),
            activityId: new ActivityId($payload['activityId']),
            scope: $scope,
            source: $source,
            propertyId: isset($payload['propertyId']) ? new PropertyId($payload['propertyId']) : null,
            providerId: isset($payload['providerId']) ? new ProviderId($payload['providerId']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
