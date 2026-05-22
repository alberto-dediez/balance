<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

use App\FoodLoss\Domain\Model\Reason\ReasonId;

class AnimalFeedAssessment
{
    public function __construct(
        private readonly ?bool $canBeAnimalFed = null,
        private readonly ?ReasonId $reasonId = null,
    ) {}

    public function canBeAnimalFed(): bool
    {
        return true === $this->canBeAnimalFed;
    }

    public function reasonId(): ?ReasonId
    {
        return $this->reasonId;
    }

    public function isAnswered(): bool
    {
        return null !== $this->canBeAnimalFed;
    }
}
