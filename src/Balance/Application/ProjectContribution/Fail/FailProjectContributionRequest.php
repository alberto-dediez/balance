<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Fail;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;

final class FailProjectContributionRequest
{
    public function __construct(
        public readonly ProjectContributionId $projectContributionId,
        public readonly string $reason = '',
    ) {}
}
