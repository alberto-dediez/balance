<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Expire;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;

/** Port used to re-check a pending contribution once its reservation window is over. */
interface ContributionExpirationScheduler
{
    public function scheduleExpiration(ProjectContributionId $projectContributionId, \DateTimeImmutable $at): void;
}
