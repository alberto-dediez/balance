<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Confirm;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;

final class ConfirmProjectContributionResponse
{
    public function __construct(
        public readonly ProjectContributionStatus $status,
        public readonly bool $changed,
    ) {}
}
