<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Expire;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;

final class ExpireProjectContributionCommand
{
    public function __construct(private string $projectContributionId) {}

    public function projectContributionId(): ProjectContributionId
    {
        return new ProjectContributionId($this->projectContributionId);
    }
}
