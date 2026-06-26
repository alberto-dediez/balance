<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CateringItem;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;

class CateringItemEmissionFactor
{
    public function __construct(
        private CateringItemEmissionFactorId $id,
        private CateringItem $cateringItem,
        private EmissionFactor $emissionFactor,
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
    ) {}

    public function id(): CateringItemEmissionFactorId
    {
        return $this->id;
    }

    public function cateringItem(): CateringItem
    {
        return $this->cateringItem;
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
