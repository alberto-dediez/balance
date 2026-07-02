<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MerchandisingItem\EmissionFactor;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemEmissionFactor;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\CarbonFootprint\Domain\Model\Unit;

class EmissionFactorRow
{
    public function __construct(
        public ?string $merchandisingItemId = null,
        public ?string $merchandisingItemName = null,
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

    public static function fromMerchandisingItem(
        MerchandisingItemId $merchandisingItemId,
        string $merchandisingItemName,
        ?MerchandisingItemEmissionFactor $factor,
        EmissionFactorGroupId $emissionFactorGroupId,
    ): self {
        return new self(
            merchandisingItemId: (string) $merchandisingItemId,
            merchandisingItemName: $merchandisingItemName,
            co2eq: $factor?->newFactor()->co2eq(),
            co2: $factor?->newFactor()->co2(),
            ch4: $factor?->newFactor()->ch4(),
            n2o: $factor?->newFactor()->n2o(),
            recycledCo2eq: $factor?->recycledFactor()->co2eq(),
            recycledCo2: $factor?->recycledFactor()->co2(),
            recycledCh4: $factor?->recycledFactor()->ch4(),
            recycledN2o: $factor?->recycledFactor()->n2o(),
            source: $factor?->newFactor()->source(),
            emissionFactorGroupId: (string) ($factor?->emissionFactorGroupId() ?? $emissionFactorGroupId),
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

    public function merchandisingItemId(): MerchandisingItemId
    {
        return new MerchandisingItemId((string) $this->merchandisingItemId);
    }

    public function emissionFactorGroupId(): EmissionFactorGroupId
    {
        return new EmissionFactorGroupId((string) $this->emissionFactorGroupId);
    }

    public function newEmissionFactor(int $year, Unit $unit, EmissionFactorGroup $emissionFactorGroup): EmissionFactor
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

    public function recycledEmissionFactor(int $year, Unit $unit, EmissionFactorGroup $emissionFactorGroup): EmissionFactor
    {
        return $emissionFactorGroup->buildEmissionFactor(
            co2eq: (string) $this->recycledCo2eq,
            co2: (string) $this->recycledCo2,
            ch4: (string) $this->recycledCh4,
            n2o: (string) $this->recycledN2o,
            year: $year,
            unit: $unit,
            source: $this->source,
        );
    }
}
