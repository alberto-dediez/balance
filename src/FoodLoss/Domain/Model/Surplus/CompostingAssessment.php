<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

use App\FoodLoss\Domain\Model\Reason\ReasonId;
use App\FoodLoss\Domain\Model\WasteManager\WasteManagerId;

class CompostingAssessment
{
    public function __construct(
        private readonly ?bool $canBeComposted = null,
        private readonly ?ReasonId $reasonId = null,
        private readonly ?WasteManagerId $wasteManagerId = null,
    ) {}

    public function canBeComposted(): bool
    {
        return true === $this->canBeComposted;
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
        return null !== $this->canBeComposted;
    }
}
