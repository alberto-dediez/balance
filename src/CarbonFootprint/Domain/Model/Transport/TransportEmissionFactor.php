<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Transport;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;

class TransportEmissionFactor
{
    public function __construct(
        private TransportEmissionFactorId $id,
        private Transport $transport,
        private EmissionFactor $emissionFactor,
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
    ) {}

    public function id(): TransportEmissionFactorId
    {
        return $this->id;
    }

    public function transport(): Transport
    {
        return $this->transport;
    }

    public function emissionFactor(): EmissionFactor
    {
        return $this->emissionFactor;
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

    public function update(EmissionFactor $emissionFactor, \DateTimeImmutable $updatedAt): void
    {
        $this->emissionFactor = $emissionFactor;
        $this->updatedAt = $updatedAt;
    }
}
