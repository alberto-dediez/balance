<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MitigationAction;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;

class MitigationActionEffect
{
    public function __construct(
        private MitigationActionEffectId $id,
        private MitigationAction $action,
        private Source $source,
        private string $itemId,
        private string $itemName,
        private float $reduction,
        private float $minimumReduction = 0.0,
        private float $maximumReduction = 1.0,
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
    ) {}

    public function id(): MitigationActionEffectId
    {
        return $this->id;
    }

    public function action(): MitigationAction
    {
        return $this->action;
    }

    public function source(): Source
    {
        return $this->source;
    }

    public function itemId(): string
    {
        return $this->itemId;
    }

    public function itemName(): string
    {
        return $this->itemName;
    }

    public function reduction(): float
    {
        return $this->reduction;
    }

    public function minimumReduction(): float
    {
        return $this->minimumReduction;
    }

    public function maximumReduction(): float
    {
        return $this->maximumReduction;
    }

    public function scope(): Scope
    {
        $scope = Scope::ofSource($this->source);
        assert(null !== $scope);

        return $scope;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function update(float $reduction, float $minimumReduction, float $maximumReduction, \DateTimeImmutable $updatedAt): void
    {
        $this->reduction = $reduction;
        $this->minimumReduction = $minimumReduction;
        $this->maximumReduction = $maximumReduction;
        $this->updatedAt = $updatedAt;
    }
}
