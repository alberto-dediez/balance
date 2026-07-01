<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmissionFactor;

use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;

final class EmissionFactor implements \Stringable
{
    private readonly string $co2eq;
    private readonly string $co2;
    private readonly string $ch4;
    private readonly string $n2o;
    private readonly int $year;
    private readonly EmissionFactorGroupId $emissionFactorGroupId;
    private readonly Unit $unit;
    private readonly ?string $source;

    public function __construct(
        string $co2eq,
        string $co2,
        string $ch4,
        string $n2o,
        int $year,
        EmissionFactorGroupId $emissionFactorGroupId,
        Unit $unit = Unit::Kilogram,
        ?string $source = null,
    ) {
        $gasesAreZero = 0.0 === (float) $co2 && 0.0 === (float) $ch4 && 0.0 === (float) $n2o;

        if ($gasesAreZero) {
            $co2 = $co2eq;
        }

        $this->co2eq = $co2eq;
        $this->co2 = $co2;
        $this->ch4 = $ch4;
        $this->n2o = $n2o;
        $this->year = $year;
        $this->emissionFactorGroupId = $emissionFactorGroupId;
        $this->unit = $unit;
        $this->source = $source;
    }

    public function co2eq(): string
    {
        return $this->co2eq;
    }

    public function co2(): string
    {
        return $this->co2;
    }

    public function ch4(): string
    {
        return $this->ch4;
    }

    public function n2o(): string
    {
        return $this->n2o;
    }

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function source(): ?string
    {
        return $this->source;
    }

    public function emissionFactorGroupId(): EmissionFactorGroupId
    {
        return $this->emissionFactorGroupId;
    }

    public function withUnit(Unit $unit): self
    {
        return new self(
            co2eq: $this->co2eq,
            co2: $this->co2,
            ch4: $this->ch4,
            n2o: $this->n2o,
            year: $this->year,
            emissionFactorGroupId: $this->emissionFactorGroupId,
            unit: $unit,
            source: $this->source,
        );
    }

    public function withYear(int $year): self
    {
        return new self(
            co2eq: $this->co2eq,
            co2: $this->co2,
            ch4: $this->ch4,
            n2o: $this->n2o,
            year: $year,
            emissionFactorGroupId: $this->emissionFactorGroupId,
            unit: $this->unit,
            source: $this->source,
        );
    }

    public function withEmissionFactorGroup(EmissionFactorGroupId $emissionFactorGroupId): self
    {
        return new self(
            co2eq: $this->co2eq,
            co2: $this->co2,
            ch4: $this->ch4,
            n2o: $this->n2o,
            year: $this->year,
            emissionFactorGroupId: $emissionFactorGroupId,
            unit: $this->unit,
            source: $this->source,
        );
    }

    public function equals(self $other): bool
    {
        return $this->co2eq === $other->co2eq()
            && $this->co2 === $other->co2()
            && $this->ch4 === $other->ch4()
            && $this->n2o === $other->n2o()
            && $this->year === $other->year()
            && $this->emissionFactorGroupId->equals($other->emissionFactorGroupId())
            && $this->unit === $other->unit()
            && $this->source === $other->source();
    }

    public function __toString(): string
    {
        return sprintf(
            '%s kg CO2eq (%d) [%s]%s',
            $this->co2eq,
            $this->year,
            $this->emissionFactorGroupId,
            null !== $this->source ? ' — ' . $this->source : '',
        );
    }
}
