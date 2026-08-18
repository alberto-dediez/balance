<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Quote;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;

final class QuoteOwnerCompensationRequest
{
    public function __construct(
        public readonly AccountId $accountId,
        public readonly ProjectId $projectId,
        public readonly float $co2eqKg,
        public readonly ?\DateTimeImmutable $now = null,
    ) {}
}
