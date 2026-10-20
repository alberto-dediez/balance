<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\GdOFactor\EmissionFactor;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorEmissionFactor;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Domain\Model\Unit;

class EmissionFactorRow
{
    public function __construct(
        public ?string $gdOFactorId = null,
        public ?string $gdOFactorName = null,
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

    public static function fromGdOFactor(
        GdOFactorId $gdOFactorId,
        string $gdOFactorName,
        ?GdOFactorEmissionFactor $factor,
        EmissionFactorGroupId $emissionFactorGroupId,
    ): self {
        return new self(
            gdOFactorId: (string) $gdOFactorId,
            gdOFactorName: $gdOFactorName,
            co2eq: $factor?->emissionFactor()->co2eq(),
            co2: $factor?->emissionFactor()->co2(),
            ch4: $factor?->emissionFactor()->ch4(),
            n2o: $factor?->emissionFactor()->n2o(),
            wellToTankCo2eq: $factor?->wellToTankFactor()->co2eq(),
            wellToTankCo2: $factor?->wellToTankFactor()->co2(),
            wellToTankCh4: $factor?->wellToTankFactor()->ch4(),
            wellToTankN2o: $factor?->wellToTankFactor()->n2o(),
            wellToTankSource: $factor?->wellToTankFactor()->source(),
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

    public function isWellToTankEmpty(): bool
    {
        return '' === (string) $this->wellToTankCo2eq
            && '' === (string) $this->wellToTankCo2
            && '' === (string) $this->wellToTankCh4
            && '' === (string) $this->wellToTankN2o;
    }

    public function gdOFactorId(): GdOFactorId
    {
        return new GdOFactorId((string) $this->gdOFactorId);
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

    public function wellToTankEmissionFactor(int $year, Unit $unit, EmissionFactorGroup $emissionFactorGroup): EmissionFactor
    {
        return $emissionFactorGroup->buildEmissionFactor(
            co2eq: self::gasOrZero($this->wellToTankCo2eq),
            co2: self::gasOrZero($this->wellToTankCo2),
            ch4: self::gasOrZero($this->wellToTankCh4),
            n2o: self::gasOrZero($this->wellToTankN2o),
            year: $year,
            unit: $unit,
            source: '' === trim((string) $this->wellToTankSource) ? null : $this->wellToTankSource,
        );
    }

    private static function gasOrZero(?string $value): string
    {
        return '' === trim((string) $value) ? '0' : (string) $value;
    }
}
