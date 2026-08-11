<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;

class ProjectContributionCreditAllocation
{
    public function __construct(
        private ProjectContributionCreditAllocationId $id,
        private ProjectContribution $projectContribution,
        private int $position,
        private CreditPurchaseId $creditPurchaseId,
        private ?string $purchaseReference = null,
        private float $tonnes = 0.0,
    ) {}

    public function id(): ProjectContributionCreditAllocationId
    {
        return $this->id;
    }

    public function projectContribution(): ProjectContribution
    {
        return $this->projectContribution;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function creditPurchaseId(): CreditPurchaseId
    {
        return $this->creditPurchaseId;
    }

    public function purchaseReference(): ?string
    {
        return $this->purchaseReference;
    }

    public function tonnes(): float
    {
        return $this->tonnes;
    }
}
