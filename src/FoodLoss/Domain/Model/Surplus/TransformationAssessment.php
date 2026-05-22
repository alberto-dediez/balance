<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

use App\FoodLoss\Domain\Model\Reason\ReasonId;

class TransformationAssessment
{
    public function __construct(
        private readonly ?bool $canBeTransformed = null,
        private readonly ?ReasonId $reasonId = null,
    ) {}

    public function canBeTransformed(): bool
    {
        return true === $this->canBeTransformed;
    }

    public function reasonId(): ?ReasonId
    {
        return $this->reasonId;
    }

    public function isAnswered(): bool
    {
        return null !== $this->canBeTransformed;
    }
}
