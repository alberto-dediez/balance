<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CalculationMethod;
use App\CarbonFootprint\Domain\Model\Unit;

final readonly class InventoryLine
{
    public function __construct(
        public Scope $scope,
        public Source $source,
        public string $itemName,
        public ?Unit $unit,
        public ?float $quantity,
        public ?float $factorCo2eq,
        public float $emissionsCo2eq,
        public ?CalculationMethod $method,
    ) {}

    public function matchingKey(): string
    {
        return implode('|', [$this->scope->value, $this->source->value, $this->itemName, $this->unit->value ?? '']);
    }

    public function hasQuantityAndFactor(): bool
    {
        return null !== $this->quantity && null !== $this->factorCo2eq;
    }
}
