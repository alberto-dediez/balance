<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\UseProfile\EmissionFactor;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileEmissionFactor;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;

class EmissionFactorRow
{
    public function __construct(
        public ?string $useProfileId = null,
        public ?string $useProfileName = null,
        public ?string $co2eq = null,
        public ?string $co2 = null,
        public ?string $ch4 = null,
        public ?string $n2o = null,
        public ?string $source = null,
        public ?string $emissionFactorGroupId = null,
    ) {}

    public static function fromUseProfile(
        UseProfileId $useProfileId,
        string $useProfileName,
        ?UseProfileEmissionFactor $factor,
        EmissionFactorGroupId $emissionFactorGroupId,
    ): self {
        return new self(
            useProfileId: (string) $useProfileId,
            useProfileName: $useProfileName,
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

    public function useProfileId(): UseProfileId
    {
        return new UseProfileId((string) $this->useProfileId);
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
