<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\Model\Project\ProjectId;

final class CreditBalance
{
    public function __construct(
        private ProjectId $projectId,
        private float $purchasedTonnes,
        private float $consumedTonnes,
        private float $amount,
        private int $purchasesCount,
    ) {}

    public function projectId(): ProjectId
    {
        return $this->projectId;
    }

    public function purchasedTonnes(): float
    {
        return $this->purchasedTonnes;
    }

    public function consumedTonnes(): float
    {
        return $this->consumedTonnes;
    }

    public function availableTonnes(): float
    {
        return $this->purchasedTonnes - $this->consumedTonnes;
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function purchasesCount(): int
    {
        return $this->purchasesCount;
    }

    public function pricePerTonne(): ?float
    {
        if (0.0 === $this->purchasedTonnes) {
            return null;
        }

        return $this->amount / $this->purchasedTonnes;
    }
}
