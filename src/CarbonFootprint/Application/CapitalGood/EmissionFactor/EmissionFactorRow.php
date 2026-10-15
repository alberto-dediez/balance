<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CapitalGood\EmissionFactor;

use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodEmissionFactor;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;

class EmissionFactorRow
{
    public function __construct(
        public ?string $capitalGoodId = null,
        public ?string $capitalGoodName = null,
        public ?string $co2eq = null,
        public ?string $co2 = null,
        public ?string $ch4 = null,
        public ?string $n2o = null,
        public ?string $source = null,
        public ?string $emissionFactorGroupId = null,
    ) {}

    public static function fromCapitalGood(
        CapitalGoodId $capitalGoodId,
        string $capitalGoodName,
        ?CapitalGoodEmissionFactor $factor,
        EmissionFactorGroupId $emissionFactorGroupId,
    ): self {
        return new self(
            capitalGoodId: (string) $capitalGoodId,
            capitalGoodName: $capitalGoodName,
            co2eq: $factor?->emissionFactor()->co2eq(),
            co2: $factor?->emissionFactor()->co2(),
            ch4: $factor?->emissionFactor()->ch4(),
            n2o: $factor?->emissionFactor()->n2o(),
            source: $factor?->emissionFactor()->source(),
            emissionFactorGroupId: (string) ($factor?->emissionFactorGroupId() ?? $emissionFactorGroupId),
        );
    }

    public function isEmpty(): bool
    {
        return '' === (string) $this->co2eq
            && '' === (string) $this->co2
            && '' === (string) $this->ch4
            && '' === (string) $this->n2o;
    }

    public function capitalGoodId(): CapitalGoodId
    {
        return new CapitalGoodId((string) $this->capitalGoodId);
    }

    public function emissionFactorGroupId(): EmissionFactorGroupId
    {
        return new EmissionFactorGroupId((string) $this->emissionFactorGroupId);
    }

    public function toEmissionFactor(int $year, Unit $unit, EmissionFactorGroup $emissionFactorGroup): EmissionFactor
    {
        return $emissionFactorGroup->buildEmissionFactor(
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
