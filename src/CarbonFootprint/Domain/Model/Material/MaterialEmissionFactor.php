<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Material;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;

class MaterialEmissionFactor
{
    public function __construct(
        private MaterialEmissionFactorId $id,
        private Material $material,
        private EmissionFactor $newFactor,
        private EmissionFactor $recycledFactor,
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
    ) {
        self::assertSameGroup($newFactor, $recycledFactor);
    }

    public function id(): MaterialEmissionFactorId
    {
        return $this->id;
    }

    public function material(): Material
    {
        return $this->material;
    }

    public function newFactor(): EmissionFactor
    {
        return $this->newFactor;
    }

    public function recycledFactor(): EmissionFactor
    {
        return $this->recycledFactor;
    }

    public function emissionFactorGroupId(): EmissionFactorGroupId
    {
        return $this->newFactor->emissionFactorGroupId();
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
        self::assertSameGroup($newFactor, $recycledFactor);

        $this->newFactor = $newFactor;
        $this->recycledFactor = $recycledFactor;
        $this->updatedAt = $updatedAt;
    }

    private static function assertSameGroup(EmissionFactor $newFactor, EmissionFactor $recycledFactor): void
    {
        if (!$newFactor->emissionFactorGroupId()->equals($recycledFactor->emissionFactorGroupId())) {
            throw new \InvalidArgumentException('New factor and recycled factor must share the same emission factor group');
        }
    }
}
