<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Material\EmissionFactor;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\CarbonFootprint\Domain\Model\Unit;

/**
 * Form-binding DTO for a single material row in the bulk emission-factors table.
 *
 * The year is NOT part of the row: it is fixed for the whole table and supplied
 * by the surrounding request. Decimal values are kept as strings to preserve the
 * precision used by the EmissionFactor value object (decimal 20,8). A row whose
 * numeric values are all blank means the material has no factor for that year.
 */
class EmissionFactorRow
{
    public function __construct(
        public ?string $materialId = null,
        public ?string $materialName = null,
        public ?string $co2eq = null,
        public ?string $co2 = null,
        public ?string $ch4 = null,
        public ?string $n2o = null,
        public ?string $source = null,
    ) {}

    public static function fromMaterial(MaterialId $materialId, string $materialName, ?EmissionFactor $emissionFactor): self
    {
        return new self(
            materialId: (string) $materialId,
            materialName: $materialName,
            co2eq: $emissionFactor?->co2eq(),
            co2: $emissionFactor?->co2(),
            ch4: $emissionFactor?->ch4(),
            n2o: $emissionFactor?->n2o(),
            source: $emissionFactor?->source(),
        );
    }

    public function isEmpty(): bool
    {
        return '' === (string) $this->co2eq
            && '' === (string) $this->co2
            && '' === (string) $this->ch4
            && '' === (string) $this->n2o;
    }

    public function materialId(): MaterialId
    {
        return new MaterialId((string) $this->materialId);
    }

    public function toEmissionFactor(int $year, Unit $unit): EmissionFactor
    {
        return new EmissionFactor(
            co2eq: (string) $this->co2eq,
            co2: (string) $this->co2,
            ch4: (string) $this->ch4,
            n2o: (string) $this->n2o,
            year: $year,
            unit: $unit,
            source: $this->source,
        );
    }
}
