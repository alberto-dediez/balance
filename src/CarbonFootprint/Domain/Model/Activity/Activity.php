<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\Shared\Domain\Model\AggregateRoot;

abstract class Activity
{
    use AggregateRoot;

    protected ActivityId $id;
    protected EmissionFactor $emissionFactor;
    protected Emissions $emissions;
    protected Quantity $quantity;
    protected \DateTimeImmutable $registeredAt;
    protected ?\DateTimeImmutable $updatedAt = null;

    public function id(): ActivityId
    {
        return $this->id;
    }

    public function emissionFactor(): EmissionFactor
    {
        return $this->emissionFactor;
    }

    public function emissions(): Emissions
    {
        return $this->emissions;
    }

    public function quantity(): Quantity
    {
        return $this->quantity;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    abstract public function remove(): void;

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
