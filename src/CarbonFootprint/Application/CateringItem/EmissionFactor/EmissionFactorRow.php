<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CateringItem\EmissionFactor;

use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Unit;

/**
 * Form-binding DTO for a single catering item row in the bulk
 * emission-factors table.
 *
 * The year is NOT part of the row: it is fixed for the whole table and supplied
 * by the surrounding request. Decimal values are kept as strings to preserve the
 * precision used by the EmissionFactor value object (decimal 20,8). A row whose
 * numeric values are all blank means the catering item has no factor for
 * that year.
 */
class EmissionFactorRow
{
    public function __construct(
        public ?string $cateringItemId = null,
        public ?string $cateringItemName = null,
        public ?string $co2eq = null,
        public ?string $co2 = null,
        public ?string $ch4 = null,
        public ?string $n2o = null,
        public ?string $source = null,
    ) {}

    public static function fromCateringItem(CateringItemId $cateringItemId, string $cateringItemName, ?EmissionFactor $emissionFactor): self
    {
        return new self(
            cateringItemId: (string) $cateringItemId,
            cateringItemName: $cateringItemName,
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

    public function cateringItemId(): CateringItemId
    {
        return new CateringItemId((string) $this->cateringItemId);
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
