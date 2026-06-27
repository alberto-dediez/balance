<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Waste\Update;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
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
