<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MachineryFuel;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;

class MachineryFuelEmissionFactor
{
    public function __construct(
        private MachineryFuelEmissionFactorId $id,
        private MachineryFuel $machineryFuel,
        private EmissionFactor $emissionFactor,
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
    ) {}

    public function id(): MachineryFuelEmissionFactorId
    {
        return $this->id;
    }

    public function machineryFuel(): MachineryFuel
    {
        return $this->machineryFuel;
    }

    public function emissionFactor(): EmissionFactor
    {
        return $this->emissionFactor;
    }

    public function year(): int
    {
        return $this->emissionFactor->year();
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function update(EmissionFactor $emissionFactor, \DateTimeImmutable $updatedAt): void
    {
        $this->emissionFactor = $emissionFactor;
        $this->updatedAt = $updatedAt;
    }
}
