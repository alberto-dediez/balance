<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Expire;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;

final class ExpireProjectContributionRequest
{
    public function __construct(
        public readonly ProjectContributionId $projectContributionId,
        public readonly ?\DateTimeImmutable $now = null,
    ) {}
}
