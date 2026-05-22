<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

use App\FoodLoss\Domain\Model\Action\ActionId;

class PreventionAssessment
{
    public function __construct(
        private readonly ?bool $canBePrevented = null,
        private readonly ?ActionId $actionId = null,
    ) {}

    public function canBePrevented(): bool
    {
        return true === $this->canBePrevented;
    }

    public function actionId(): ?ActionId
    {
        return $this->actionId;
    }

    public function isAnswered(): bool
    {
        return null !== $this->canBePrevented;
    }
}
