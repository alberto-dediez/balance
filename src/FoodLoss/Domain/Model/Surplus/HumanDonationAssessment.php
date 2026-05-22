<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

use App\FoodLoss\Domain\Model\Reason\ReasonId;

class HumanDonationAssessment
{
    public function __construct(
        private readonly ?bool $canBeHumanDonated = null,
        private readonly ?ReasonId $reasonId = null,
    ) {}

    public function canBeHumanDonated(): bool
    {
        return true === $this->canBeHumanDonated;
    }

    public function reasonId(): ?ReasonId
    {
        return $this->reasonId;
    }

    public function isAnswered(): bool
    {
        return null !== $this->canBeHumanDonated;
    }
}
