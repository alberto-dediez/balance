<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

use App\Balance\Domain\Model\Project\ProjectId;

final class CreditBalance
{
    private const TONNE_TOLERANCE = 0.000001;

    public function __construct(
        private ProjectId $projectId,
        private float $purchasedTonnes,
        private float $consumedTonnes,
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

    /** Consuming more than what was purchased breaks the invariant of the balance and is never a valid state. */
    public function availableTonnes(): float
    {
        $availableTonnes = $this->purchasedTonnes - $this->consumedTonnes;

        if ($availableTonnes < -self::TONNE_TOLERANCE) {
            throw new NegativeCreditBalanceException($this->projectId, $this->purchasedTonnes, $this->consumedTonnes);
        }

        return $availableTonnes;
    }

    public function purchasesCount(): int
    {
        return $this->purchasesCount;
    }
}
