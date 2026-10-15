<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Process\EmissionFactor;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Process\ProcessEmissionFactor;
use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\CarbonFootprint\Domain\Model\Unit;

class EmissionFactorRow
{
    public function __construct(
        public ?string $processId = null,
        public ?string $processName = null,
        public ?string $co2eq = null,
        public ?string $co2 = null,
        public ?string $ch4 = null,
        public ?string $n2o = null,
        public ?string $source = null,
        public ?string $emissionFactorGroupId = null,
    ) {}

    public static function fromProcess(
        ProcessId $processId,
        string $processName,
        ?ProcessEmissionFactor $factor,
        EmissionFactorGroupId $emissionFactorGroupId,
    ): self {
        return new self(
            processId: (string) $processId,
            processName: $processName,
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

    public function processId(): ProcessId
    {
        return new ProcessId((string) $this->processId);
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
