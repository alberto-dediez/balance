<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MerchandisingItem\EmissionFactor;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\CarbonFootprint\Domain\Model\Unit;

/**
 * Form-binding DTO for a single merchandising item row in the bulk
 * emission-factors table.
 *
 * The year is NOT part of the row: it is fixed for the whole table and supplied
 * by the surrounding request. Decimal values are kept as strings to preserve the
 * precision used by the EmissionFactor value object (decimal 20,8). A row whose
 * numeric values are all blank means the merchandising item has no factor for
 * that year.
 */
class EmissionFactorRow
{
    public function __construct(
        public ?string $merchandisingItemId = null,
        public ?string $merchandisingItemName = null,
        public ?string $co2eq = null,
        public ?string $co2 = null,
        public ?string $ch4 = null,
        public ?string $n2o = null,
        public ?string $source = null,
    ) {}

    public static function fromMerchandisingItem(MerchandisingItemId $merchandisingItemId, string $merchandisingItemName, ?EmissionFactor $emissionFactor): self
    {
        return new self(
            merchandisingItemId: (string) $merchandisingItemId,
            merchandisingItemName: $merchandisingItemName,
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

    public function merchandisingItemId(): MerchandisingItemId
    {
        return new MerchandisingItemId((string) $this->merchandisingItemId);
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
