<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model;

final class Emissions implements \Stringable
{
    public function __construct(
        private readonly string $co2eq = '0',
        private readonly string $co2 = '0',
        private readonly string $ch4 = '0',
        private readonly string $n2o = '0',
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

    public function equals(self $other): bool
    {
        return $this->co2eq === $other->co2eq()
            && $this->co2 === $other->co2()
            && $this->ch4 === $other->ch4()
            && $this->n2o === $other->n2o();
    }

    public function __toString(): string
    {
        return sprintf('%s kg CO2eq', $this->co2eq);
    }
}
