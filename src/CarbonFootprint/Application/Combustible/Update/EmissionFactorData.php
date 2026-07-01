<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Combustible\Update;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\Gwp;
use App\CarbonFootprint\Domain\Model\Unit;

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
}
