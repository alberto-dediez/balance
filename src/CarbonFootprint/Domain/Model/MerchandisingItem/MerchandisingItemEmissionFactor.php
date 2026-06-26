<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MerchandisingItem;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;

class MerchandisingItemEmissionFactor
{
    public function __construct(
        private MerchandisingItemEmissionFactorId $id,
        private MerchandisingItem $merchandisingItem,
        private EmissionFactor $newFactor,
        private EmissionFactor $recycledFactor,
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
    ) {}

    public function id(): MerchandisingItemEmissionFactorId
    {
        return $this->id;
    }

    public function merchandisingItem(): MerchandisingItem
    {
        return $this->merchandisingItem;
    }

    public function newFactor(): EmissionFactor
    {
        return $this->newFactor;
    }

    public function recycledFactor(): EmissionFactor
    {
        return $this->recycledFactor;
    }

    public function year(): int
    {
        return $this->newFactor->year();
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function update(EmissionFactor $newFactor, EmissionFactor $recycledFactor, \DateTimeImmutable $updatedAt): void
    {
        $this->newFactor = $newFactor;
        $this->recycledFactor = $recycledFactor;
        $this->updatedAt = $updatedAt;
    }
}
