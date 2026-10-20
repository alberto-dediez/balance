<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\ElectricityMix;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;

class ElectricityMixEmissionFactor
{
    public function __construct(
        private ElectricityMixEmissionFactorId $id,
        private ElectricityMix $electricityMix,
        private EmissionFactor $emissionFactor,
        private EmissionFactor $wellToTankFactor,
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
    ) {
        self::assertSameYearAndGroup($emissionFactor, $wellToTankFactor);
    }

    public function id(): ElectricityMixEmissionFactorId
    {
        return $this->id;
    }

    public function electricityMix(): ElectricityMix
    {
        return $this->electricityMix;
    }

    public function emissionFactor(): EmissionFactor
    {
        return $this->emissionFactor;
    }

    public function wellToTankFactor(): EmissionFactor
    {
        return $this->wellToTankFactor;
    }

    public function emissionFactorGroupId(): EmissionFactorGroupId
    {
        return $this->emissionFactor->emissionFactorGroupId();
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

    public function update(EmissionFactor $emissionFactor, EmissionFactor $wellToTankFactor, \DateTimeImmutable $updatedAt): void
    {
        self::assertSameYearAndGroup($emissionFactor, $wellToTankFactor);

        $this->emissionFactor = $emissionFactor;
        $this->wellToTankFactor = $wellToTankFactor;
        $this->updatedAt = $updatedAt;
    }

    private static function assertSameYearAndGroup(EmissionFactor $emissionFactor, EmissionFactor $wellToTankFactor): void
    {
        if ($emissionFactor->year() !== $wellToTankFactor->year()) {
            throw new \InvalidArgumentException('Emission factor and well-to-tank factor must share the same year');
        }

        if (!$emissionFactor->emissionFactorGroupId()->equals($wellToTankFactor->emissionFactorGroupId())) {
            throw new \InvalidArgumentException('Emission factor and well-to-tank factor must share the same emission factor group');
        }
    }
}
