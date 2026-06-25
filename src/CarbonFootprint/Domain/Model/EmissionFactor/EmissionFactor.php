<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmissionFactor;

use App\CarbonFootprint\Domain\Model\Unit;

final class EmissionFactor implements \Stringable
{
    public function __construct(
        private readonly string $co2eq,
        private readonly string $co2,
        private readonly string $ch4,
        private readonly string $n2o,
        private readonly int $year,
        private readonly Unit $unit = Unit::Kilogram,
        private readonly ?string $source = null,
    ) {}

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

    public function withUnit(Unit $unit): self
    {
        return new self(
            co2eq: $this->co2eq,
            co2: $this->co2,
            ch4: $this->ch4,
            n2o: $this->n2o,
            year: $this->year,
            unit: $unit,
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
            && $this->unit === $other->unit()
            && $this->source === $other->source();
    }

    public function __toString(): string
    {
        return sprintf(
            '%s kg CO2eq (%d)%s',
            $this->co2eq,
            $this->year,
            null !== $this->source ? ' — ' . $this->source : '',
        );
    }
}
