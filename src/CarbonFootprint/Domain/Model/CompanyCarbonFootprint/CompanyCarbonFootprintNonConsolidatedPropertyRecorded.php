<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CompanyCarbonFootprintNonConsolidatedPropertyRecorded extends DomainEvent
{
    protected static string $eventName = 'companyCarbonFootprintNonConsolidatedPropertyRecorded';

    public function __construct(
        private CompanyCarbonFootprintId $companyCarbonFootprintId,
        private PropertyId $propertyId,
        private Source $source,
        private ?PropertyCarbonFootprintId $propertyCarbonFootprintId,
        private Emissions $calculatedEmissions,
        private ?string $manualCo2eq = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function companyCarbonFootprintId(): CompanyCarbonFootprintId
    {
        return $this->companyCarbonFootprintId;
    }

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
    }

    public function source(): Source
    {
        return $this->source;
    }

    public function propertyCarbonFootprintId(): ?PropertyCarbonFootprintId
    {
        return $this->propertyCarbonFootprintId;
    }

    public function calculatedEmissions(): Emissions
    {
        return $this->calculatedEmissions;
    }

    public function manualCo2eq(): ?string
    {
        return $this->manualCo2eq;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->companyCarbonFootprintId;
    }

    public function payload(): array
    {
        return [
            'companyCarbonFootprintId' => (string) $this->companyCarbonFootprintId,
            'propertyId' => (string) $this->propertyId,
            'source' => $this->source->value,
            'propertyCarbonFootprintId' => $this->propertyCarbonFootprintId instanceof PropertyCarbonFootprintId ? (string) $this->propertyCarbonFootprintId : null,
            'calculatedCo2eq' => $this->calculatedEmissions->co2eq(),
            'calculatedCo2' => $this->calculatedEmissions->co2(),
            'calculatedCh4' => $this->calculatedEmissions->ch4(),
            'calculatedN2o' => $this->calculatedEmissions->n2o(),
            'manualCo2eq' => $this->manualCo2eq,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $source = Source::tryFrom($payload['source']);
        assert(null !== $source);

        return new self(
            companyCarbonFootprintId: new CompanyCarbonFootprintId($payload['companyCarbonFootprintId']),
            propertyId: new PropertyId($payload['propertyId']),
            source: $source,
            propertyCarbonFootprintId: isset($payload['propertyCarbonFootprintId']) ? new PropertyCarbonFootprintId($payload['propertyCarbonFootprintId']) : null,
            calculatedEmissions: new Emissions(
                co2eq: (string) $payload['calculatedCo2eq'],
                co2: (string) $payload['calculatedCo2'],
                ch4: (string) $payload['calculatedCh4'],
                n2o: (string) $payload['calculatedN2o'],
            ),
            manualCo2eq: $payload['manualCo2eq'] ?? null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
