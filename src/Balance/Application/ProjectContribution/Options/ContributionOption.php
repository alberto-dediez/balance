<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Options;

use App\Balance\Domain\Model\Project\Project;

final class ContributionOption
{
    public function __construct(
        public readonly Project $project,
        public readonly float $pricePerTonne,
        public readonly float $costPerTonne,
        public readonly float $availableTonnes,
        public readonly bool $limitedByCredit,
        public readonly ?float $suggestedAmount = null,
    ) {}
}
