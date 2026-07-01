<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Material\EmissionFactor;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\Gwp;
use App\CarbonFootprint\Domain\Model\Material\MaterialEmissionFactor;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\CarbonFootprint\Domain\Model\Unit;

class EmissionFactorRow
{
    public function __construct(
        public ?string $materialId = null,
        public ?string $materialName = null,
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

    public static function fromMaterial(MaterialId $materialId, string $materialName, ?MaterialEmissionFactor $factor): self
    {
        return new self(
            materialId: (string) $materialId,
            materialName: $materialName,
            co2eq: $factor?->newFactor()->co2eq(),
            co2: $factor?->newFactor()->co2(),
            ch4: $factor?->newFactor()->ch4(),
            n2o: $factor?->newFactor()->n2o(),
            recycledCo2eq: $factor?->recycledFactor()->co2eq(),
            recycledCo2: $factor?->recycledFactor()->co2(),
            recycledCh4: $factor?->recycledFactor()->ch4(),
            recycledN2o: $factor?->recycledFactor()->n2o(),
            source: $factor?->newFactor()->source(),
        );
    }

    public function isEmpty(): bool
    {
        return '' === (string) $this->co2eq
            && '' === (string) $this->co2
            && '' === (string) $this->ch4
            && '' === (string) $this->n2o
            && '' === (string) $this->recycledCo2eq
            && '' === (string) $this->recycledCo2
            && '' === (string) $this->recycledCh4
            && '' === (string) $this->recycledN2o;
    }

    public function materialId(): MaterialId
    {
        return new MaterialId((string) $this->materialId);
    }

    public function newEmissionFactor(int $year, Unit $unit): EmissionFactor
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
            year: $year,
            emissionFactorGroupId: EmissionFactorGroupId::default(),
            unit: $unit,
            source: $this->source,
        );
    }

    public function recycledEmissionFactor(int $year, Unit $unit): EmissionFactor
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
            year: $year,
            emissionFactorGroupId: EmissionFactorGroupId::default(),
            unit: $unit,
            source: $this->source,
        );
    }
}
