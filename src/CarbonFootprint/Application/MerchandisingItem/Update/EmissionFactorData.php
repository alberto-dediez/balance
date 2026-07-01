<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MerchandisingItem\Update;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemEmissionFactor;
use App\CarbonFootprint\Domain\Model\Unit;

/**
 * Form-binding DTO for a single emission factor row in the dynamic table.
 * Decimal values are kept as strings to preserve the precision used by the
 * EmissionFactor value object (decimal 20,8).
 *
 * The unit is intentionally NOT user-editable: it is derived from the owning
 * MerchandisingItem's unit when the value object is built. Year, source and the emission
 * factor group are shared between the new-fabrication and recycled factors.
 */
class EmissionFactorData
{
    public function __construct(
        public ?int $year = null,
        public ?string $co2eq = null,
        public ?string $co2 = null,
        public ?string $ch4 = null,
        public ?string $n2o = null,
        public ?string $recycledCo2eq = null,
        public ?string $recycledCo2 = null,
        public ?string $recycledCh4 = null,
        public ?string $recycledN2o = null,
        public ?string $source = null,
        public ?string $emissionFactorGroupId = null,
    ) {}

    public static function fromMerchandisingItemEmissionFactor(MerchandisingItemEmissionFactor $factor): self
    {
        return new self(
            year: $factor->newFactor()->year(),
            co2eq: $factor->newFactor()->co2eq(),
            co2: $factor->newFactor()->co2(),
            ch4: $factor->newFactor()->ch4(),
            n2o: $factor->newFactor()->n2o(),
            recycledCo2eq: $factor->recycledFactor()->co2eq(),
            recycledCo2: $factor->recycledFactor()->co2(),
            recycledCh4: $factor->recycledFactor()->ch4(),
            recycledN2o: $factor->recycledFactor()->n2o(),
            source: $factor->newFactor()->source(),
            emissionFactorGroupId: (string) $factor->newFactor()->emissionFactorGroupId(),
        );
    }

    public function newEmissionFactor(Unit $unit, EmissionFactorGroup $emissionFactorGroup): EmissionFactor
    {
        return $emissionFactorGroup->buildEmissionFactor(
            co2: (string) $this->co2,
            ch4: (string) $this->ch4,
            n2o: (string) $this->n2o,
            year: (int) $this->year,
            unit: $unit,
            source: $this->source,
        );
    }

    public function recycledEmissionFactor(Unit $unit, EmissionFactorGroup $emissionFactorGroup): EmissionFactor
    {
        return $emissionFactorGroup->buildEmissionFactor(
            co2: (string) $this->recycledCo2,
            ch4: (string) $this->recycledCh4,
            n2o: (string) $this->recycledN2o,
            year: (int) $this->year,
            unit: $unit,
            source: $this->source,
        );
    }
}
