<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Fail;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;

final class FailProjectContributionResponse
{
    public function __construct(
        public readonly ProjectContributionStatus $status,
        public readonly bool $changed,
    ) {}
}
