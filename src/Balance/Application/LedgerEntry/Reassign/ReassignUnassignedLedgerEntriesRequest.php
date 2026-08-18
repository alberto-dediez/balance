<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\Reassign;

use App\Balance\Domain\Model\ProjectContribution\AllocatedCreditLine;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;

final class ReassignUnassignedLedgerEntriesRequest
{
    /** @param list<AllocatedCreditLine> $lines */
    public function __construct(
        public readonly ProjectContributionId $projectContributionId,
        public readonly array $lines,
    ) {}
}
