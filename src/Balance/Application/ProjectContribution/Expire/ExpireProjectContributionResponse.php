<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Expire;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;

final class ExpireProjectContributionResponse
{
    public function __construct(
        public readonly ProjectContributionStatus $status,
        public readonly bool $changed,
    ) {}
}
