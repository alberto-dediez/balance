<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\Treasury\View;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;

final readonly class CreditPurchaseRow
{
    public function __construct(
        public CreditPurchase $creditPurchase,
        public float $allocatedTonnes,
    ) {}

    public function purchasedTonnes(): float
    {
        return $this->creditPurchase->purchasedTonnes() ?? 0.0;
    }

    public function idleTonnes(): float
    {
        return round($this->purchasedTonnes() - $this->allocatedTonnes, 6);
    }

    public function idleAmount(): ?float
    {
        $pricePerTonne = $this->creditPurchase->pricePerTonne();

        if (null === $pricePerTonne) {
            return null;
        }

        return round($this->idleTonnes() * $pricePerTonne, 2);
    }

    public function soldAmount(): ?float
    {
        $pricePerTonne = $this->creditPurchase->pricePerTonne();

        if (null === $pricePerTonne) {
            return null;
        }

        return round($this->allocatedTonnes * $pricePerTonne, 2);
    }
}
