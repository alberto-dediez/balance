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

    public function add(self $other): self
    {
        return new self(
            (string) ((float) $this->co2eq + (float) $other->co2eq()),
            (string) ((float) $this->co2 + (float) $other->co2()),
            (string) ((float) $this->ch4 + (float) $other->ch4()),
            (string) ((float) $this->n2o + (float) $other->n2o()),
        );
    }

    public function dividedBy(int $divisor): self
    {
        if ($divisor <= 0) {
            throw new \InvalidArgumentException('Divisor must be greater than zero');
        }

        return new self(
            (string) ((float) $this->co2eq / $divisor),
            (string) ((float) $this->co2 / $divisor),
            (string) ((float) $this->ch4 / $divisor),
            (string) ((float) $this->n2o / $divisor),
        );
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
