<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Material\Update;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Unit;

/**
 * Form-binding DTO for a single emission factor row in the dynamic table.
 * Decimal values are kept as strings to preserve the precision used by the
 * EmissionFactor value object (decimal 20,8).
 *
 * The unit is intentionally NOT user-editable: it is derived from the owning
 * Material's unit when the value object is built.
 */
class EmissionFactorData
{
    public function __construct(
        public ?int $year = null,
        public ?string $co2eq = null,
        public ?string $co2 = null,
        public ?string $ch4 = null,
        public ?string $n2o = null,
        public ?string $source = null,
    ) {}

    public static function fromEmissionFactor(EmissionFactor $emissionFactor): self
    {
        return new self(
            year: $emissionFactor->year(),
            co2eq: $emissionFactor->co2eq(),
            co2: $emissionFactor->co2(),
            ch4: $emissionFactor->ch4(),
            n2o: $emissionFactor->n2o(),
            source: $emissionFactor->source(),
        );
    }

    public function toEmissionFactor(Unit $unit): EmissionFactor
    {
        return new EmissionFactor(
            co2eq: (string) $this->co2eq,
            co2: (string) $this->co2,
            ch4: (string) $this->ch4,
            n2o: (string) $this->n2o,
            year: (int) $this->year,
            unit: $unit,
            source: $this->source,
        );
    }
}
