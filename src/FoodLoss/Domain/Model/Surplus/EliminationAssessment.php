<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

class EliminationAssessment
{
    public function __construct(
        private readonly ?bool $canBeEliminated = null,
    ) {}

    public function canBeEliminated(): bool
    {
        return true === $this->canBeEliminated;
    }

    public function isAnswered(): bool
    {
        return null !== $this->canBeEliminated;
    }
}
