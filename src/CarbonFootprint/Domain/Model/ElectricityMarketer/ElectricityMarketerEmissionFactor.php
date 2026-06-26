<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ElectricityMarketer;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;

class ElectricityMarketerEmissionFactor
{
    public function __construct(
        private ElectricityMarketerEmissionFactorId $id,
        private ElectricityMarketer $electricityMarketer,
        private EmissionFactor $emissionFactor,
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
    ) {}

    public function id(): ElectricityMarketerEmissionFactorId
    {
        return $this->id;
    }

    public function electricityMarketer(): ElectricityMarketer
    {
        return $this->electricityMarketer;
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
