<?php

declare(strict_types=1);

namespace App\Balance\Application\LedgerEntry\Record;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;

final class RecordContributionLedgerEntriesRequest
{
    public function __construct(
        public readonly ProjectContributionId $projectContributionId,
    ) {}
}
