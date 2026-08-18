<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\Treasury\View;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchase;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionCreditAllocation;

final class BeneficiaryDetailView
{
    /** @var list<CreditPurchaseRow> */
    private array $purchases = [];

    /**
     * @param array<CreditPurchase>                                    $creditPurchases
     * @param array<string, list<ProjectContributionCreditAllocation>> $allocationsByPurchase
     */
    public function __construct(array $creditPurchases, array $allocationsByPurchase)
    {
        foreach ($creditPurchases as $creditPurchase) {
            $allocated = 0.0;

            foreach ($allocationsByPurchase[(string) $creditPurchase->id()] ?? [] as $allocation) {
                $allocated += $allocation->tonnes();
            }

            $this->purchases[] = new CreditPurchaseRow($creditPurchase, $allocated);
        }
    }

    /** @return list<CreditPurchaseRow> */
    public function purchases(): array
    {
        return $this->purchases;
    }

    public function purchasedTonnes(): float
    {
        return round(array_sum(array_map(static fn(CreditPurchaseRow $row): float => $row->purchasedTonnes(), $this->purchases)), 6);
    }

    public function soldTonnes(): float
    {
        return round(array_sum(array_map(static fn(CreditPurchaseRow $row): float => $row->allocatedTonnes, $this->purchases)), 6);
    }

    public function idleTonnes(): float
    {
        return round($this->purchasedTonnes() - $this->soldTonnes(), 6);
    }

    public function purchasedAmount(): ?float
    {
        return $this->sumAmounts(static fn(CreditPurchaseRow $row): ?float => $row->creditPurchase->amount());
    }

    public function idleAmount(): ?float
    {
        return $this->sumAmounts(static fn(CreditPurchaseRow $row): ?float => $row->idleAmount());
    }

    /** @param callable(CreditPurchaseRow): ?float $amountOf */
    private function sumAmounts(callable $amountOf): ?float
    {
        $total = 0.0;
        $known = false;

        foreach ($this->purchases as $row) {
            $amount = $amountOf($row);

            if (null === $amount) {
                continue;
            }

            $total += $amount;
            $known = true;
        }

        return $known ? round($total, 2) : null;
    }
}
