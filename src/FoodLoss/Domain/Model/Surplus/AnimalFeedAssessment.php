<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerId;

class AnimalFeedAssessment
{
    public function __construct(
        private readonly ?bool $canBeAnimalFed = null,
        private readonly ?ReasonId $reasonId = null,
        private readonly ?WasteManagerId $wasteManagerId = null,
    ) {}

    public function canBeAnimalFed(): bool
    {
        return true === $this->canBeAnimalFed;
    }

    public function reasonId(): ?ReasonId
    {
        return $this->reasonId;
    }

    public function wasteManagerId(): ?WasteManagerId
    {
        return $this->wasteManagerId;
    }

    public function isAnswered(): bool
    {
        return null !== $this->canBeAnimalFed;
    }
}
