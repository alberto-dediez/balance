<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model;

final class Quantity implements \Stringable
{
    public function __construct(
        private readonly float $value,
        private readonly Unit $unit,
    ) {}

    public function value(): float
    {
        return $this->value;
    }

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function magnitude(): Magnitude
    {
        return $this->unit->magnitude();
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value
            && $this->unit === $other->unit;
    }

    public function __toString(): string
    {
        return $this->value . ' ' . $this->unit->value;
    }
}
