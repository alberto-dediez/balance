<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Quote;

use App\Balance\Domain\Model\Project\Project;
use App\Balance\Domain\Model\ProjectContribution\ContributionAmounts;

final class QuoteContributionResponse
{
    public function __construct(
        public readonly Project $project,
        public readonly ContributionAmounts $amounts,
        public readonly bool $limitedByCredit,
        public readonly float $availableTonnes,
    ) {}
}
