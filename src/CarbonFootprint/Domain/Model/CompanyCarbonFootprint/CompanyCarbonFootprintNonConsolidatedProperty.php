<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\NonConsolidatedPropertyLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Property\Domain\Model\Property\PropertyId;

class CompanyCarbonFootprintNonConsolidatedProperty
{
    public function __construct(
        private CompanyCarbonFootprintNonConsolidatedPropertyId $id,
        private CompanyCarbonFootprint $companyCarbonFootprint,
        private PropertyId $propertyId,
        private Source $source,
        private ?PropertyCarbonFootprintId $propertyCarbonFootprintId = null,
        private Emissions $calculatedEmissions = new Emissions(),
        private ?string $manualCo2eq = null,
        private \DateTimeImmutable $recordedAt = new \DateTimeImmutable('NOW'),
    ) {}

    public function id(): CompanyCarbonFootprintNonConsolidatedPropertyId
    {
        return $this->id;
    }

    public function companyCarbonFootprint(): CompanyCarbonFootprint
    {
        return $this->companyCarbonFootprint;
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

    public function recordedAt(): \DateTimeImmutable
    {
        return $this->recordedAt;
    }

    public function update(
        Source $source,
        ?PropertyCarbonFootprintId $propertyCarbonFootprintId,
        Emissions $calculatedEmissions,
        ?string $manualCo2eq,
        \DateTimeImmutable $recordedAt,
    ): void {
        $this->source = $source;
        $this->propertyCarbonFootprintId = $propertyCarbonFootprintId;
        $this->calculatedEmissions = $calculatedEmissions;
        $this->manualCo2eq = $manualCo2eq;
        $this->recordedAt = $recordedAt;
    }

    public function emissions(): Emissions
    {
        return null !== $this->manualCo2eq ? new Emissions($this->manualCo2eq) : $this->calculatedEmissions;
    }

    public function toLine(): NonConsolidatedPropertyLine
    {
        return new NonConsolidatedPropertyLine(
            source: $this->source,
            propertyId: $this->propertyId,
            propertyCarbonFootprintId: $this->propertyCarbonFootprintId,
            calculatedEmissions: $this->calculatedEmissions,
            manualCo2eq: $this->manualCo2eq,
            emissions: $this->emissions(),
        );
    }
}
