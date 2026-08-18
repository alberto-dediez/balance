<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Quote;

use App\Balance\Domain\Model\Project\Project;

final class QuoteOwnerCompensationResponse
{
    public function __construct(
        public readonly Project $project,
        public readonly float $tonnes,
        public readonly float $co2eqKg,
        public readonly float $pricePerTonne,
        public readonly float $costPerTonne,
        public readonly float $contributionAmount,
        public readonly float $ownAvailableTonnes,
        public readonly bool $exceedsCredit,
    ) {}
}
