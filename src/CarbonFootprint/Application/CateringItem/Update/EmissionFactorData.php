<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CateringItem\Update;

use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemEmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\Gwp;
use App\CarbonFootprint\Domain\Model\Unit;

/**
 * Form-binding DTO for a single emission factor row in the dynamic table.
 * Decimal values are kept as strings to preserve the precision used by the
 * EmissionFactor value object (decimal 20,8).
 *
 * The unit is intentionally NOT user-editable: it is derived from the owning
 * CateringItem's unit when the value object is built. Year and source are shared
 * between the new-fabrication and recycled factors.
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
    ) {}

    public static function fromCateringItemEmissionFactor(CateringItemEmissionFactor $factor): self
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
        );
    }

    public function newEmissionFactor(Unit $unit): EmissionFactor
    {
        $co2 = (string) $this->co2;
        $ch4 = (string) $this->ch4;
        $n2o = (string) $this->n2o;
        $co2eq = (string) $this->co2eq;

        if (0.0 === (float) $co2eq) {
            // Temporary FASE 2 compatibility fallback: real EmissionFactorGroup resolution lands in FASE 3/7/8/9.
            $co2eq = Gwp::ar6()->co2eqFor($co2, $ch4, $n2o);
        }

        return new EmissionFactor(
            co2eq: $co2eq,
            co2: $co2,
            ch4: $ch4,
            n2o: $n2o,
            year: (int) $this->year,
            emissionFactorGroupId: EmissionFactorGroupId::default(),
            unit: $unit,
            source: $this->source,
        );
    }

    public function recycledEmissionFactor(Unit $unit): EmissionFactor
    {
        $co2 = (string) $this->recycledCo2;
        $ch4 = (string) $this->recycledCh4;
        $n2o = (string) $this->recycledN2o;
        $co2eq = (string) $this->recycledCo2eq;

        if (0.0 === (float) $co2eq) {
            // Temporary FASE 2 compatibility fallback: real EmissionFactorGroup resolution lands in FASE 3/7/8/9.
            $co2eq = Gwp::ar6()->co2eqFor($co2, $ch4, $n2o);
        }

        return new EmissionFactor(
            co2eq: $co2eq,
            co2: $co2,
            ch4: $ch4,
            n2o: $n2o,
            year: (int) $this->year,
            emissionFactorGroupId: EmissionFactorGroupId::default(),
            unit: $unit,
            source: $this->source,
        );
    }
}
