<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Allocate;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;

final class AllocateContributionCreditsRequest
{
    public function __construct(
        public readonly ProjectContributionId $projectContributionId,
    ) {}
}
