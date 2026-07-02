<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmissionFactorGroup;

final class Gwp
{
    public function __construct(
        private float $gwpCo2 = 1.0,
        private float $gwpCh4 = 0.0,
        private float $gwpN2O = 0.0,
    ) {}

    public function gwpCo2(): float
    {
        return $this->gwpCo2;
    }

    public function gwpCh4(): float
    {
        return $this->gwpCh4;
    }

    public function gwpN2O(): float
    {
        return $this->gwpN2O;
    }

    public function equals(self $other): bool
    {
        return $this->gwpCo2 === $other->gwpCo2()
            && $this->gwpCh4 === $other->gwpCh4()
            && $this->gwpN2O === $other->gwpN2O();
    }

    public function co2eqFor(string $co2, string $ch4, string $n2o): string
    {
        return (string) ((float) $co2 * $this->gwpCo2 + (float) $ch4 / 1000 * $this->gwpCh4 + (float) $n2o / 1000 * $this->gwpN2O);
    }
}
