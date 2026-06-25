<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Material;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;

class MaterialEmissionFactor
{
    public function __construct(
        private MaterialEmissionFactorId $id,
        private Material $material,
        private EmissionFactor $emissionFactor,
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
    ) {}

    public function id(): MaterialEmissionFactorId
    {
        return $this->id;
    }

    public function material(): Material
    {
        return $this->material;
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
