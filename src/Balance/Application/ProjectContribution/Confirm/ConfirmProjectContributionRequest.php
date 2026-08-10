<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Confirm;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;

final class ConfirmProjectContributionRequest
{
    public function __construct(public readonly ProjectContributionId $projectContributionId) {}
}
