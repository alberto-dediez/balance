<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\LedgerEntry;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Payments\Domain\Model\Payout\PayoutId;
use App\Shared\Domain\Model\Filter;

class LedgerEntryFilter
{
    use Filter;

    /** @param list<LedgerEntryType>|null $types */
    public function __construct(
        public ?LedgerEntryType $type = null,
        public ?array $types = null,
        public ?LedgerBeneficiary $beneficiary = null,
        public ?string $beneficiaryId = null,
        public ?ProjectContributionId $projectContributionId = null,
        public ?ProjectId $projectId = null,
        public ?CreditPurchaseId $creditPurchaseId = null,
        public ?PayoutId $payoutId = null,
        public ?bool $settled = null,
        public ?\DateTimeImmutable $from = null,
        public ?\DateTimeImmutable $to = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
