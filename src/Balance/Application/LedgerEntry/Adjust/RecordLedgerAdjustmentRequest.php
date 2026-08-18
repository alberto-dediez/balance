<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\Adjust;

use App\Balance\Domain\Model\LedgerEntry\LedgerBeneficiary;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryId;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;

final class RecordLedgerAdjustmentRequest
{
    public function __construct(
        public readonly LedgerBeneficiary $beneficiary,
        public readonly ?string $beneficiaryId,
        public readonly float $amount,
        public readonly string $concept,
        public readonly string $currency = 'EUR',
        public readonly ?ProjectContributionId $projectContributionId = null,
        public readonly ?ProjectId $projectId = null,
        public readonly ?LedgerEntryId $reversesEntryId = null,
    ) {}
}
