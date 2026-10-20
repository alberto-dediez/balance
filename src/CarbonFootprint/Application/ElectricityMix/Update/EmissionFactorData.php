<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMix\Update;

use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixEmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\Unit;

class EmissionFactorData
{
    public function __construct(
        public ?int $year = null,
        public ?string $co2eq = null,
        public ?string $co2 = null,
        public ?string $ch4 = null,
        public ?string $n2o = null,
        public ?string $wellToTankCo2eq = null,
        public ?string $wellToTankCo2 = null,
        public ?string $wellToTankCh4 = null,
        public ?string $wellToTankN2o = null,
        public ?string $wellToTankSource = null,
        public ?string $source = null,
        public ?string $emissionFactorGroupId = null,
    ) {}

    public static function fromEmissionFactor(ElectricityMixEmissionFactor $factor): self
    {
        $emissionFactor = $factor->emissionFactor();
        $wellToTankFactor = $factor->wellToTankFactor();

        return new self(
            year: $emissionFactor->year(),
            co2eq: $emissionFactor->co2eq(),
            co2: $emissionFactor->co2(),
            ch4: $emissionFactor->ch4(),
            n2o: $emissionFactor->n2o(),
            wellToTankCo2eq: $wellToTankFactor->co2eq(),
            wellToTankCo2: $wellToTankFactor->co2(),
            wellToTankCh4: $wellToTankFactor->ch4(),
            wellToTankN2o: $wellToTankFactor->n2o(),
            wellToTankSource: $wellToTankFactor->source(),
            source: $emissionFactor->source(),
            emissionFactorGroupId: (string) $emissionFactor->emissionFactorGroupId(),
        );
    }

    public function toEmissionFactor(Unit $unit, EmissionFactorGroup $emissionFactorGroup): EmissionFactor
    {
        return $emissionFactorGroup->buildEmissionFactor(
            co2eq: (string) $this->co2eq,
            co2: (string) $this->co2,
            ch4: (string) $this->ch4,
            n2o: (string) $this->n2o,
            year: (int) $this->year,
            unit: $unit,
            source: $this->source,
        );
    }

    public function wellToTankEmissionFactor(Unit $unit, EmissionFactorGroup $emissionFactorGroup): EmissionFactor
    {
        return $emissionFactorGroup->buildEmissionFactor(
            co2eq: self::gasOrZero($this->wellToTankCo2eq),
            co2: self::gasOrZero($this->wellToTankCo2),
            ch4: self::gasOrZero($this->wellToTankCh4),
            n2o: self::gasOrZero($this->wellToTankN2o),
            year: (int) $this->year,
            unit: $unit,
            source: '' === trim((string) $this->wellToTankSource) ? null : $this->wellToTankSource,
        );
    }

    private static function gasOrZero(?string $value): string
    {
        return '' === trim((string) $value) ? '0' : (string) $value;
    }
}
