<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ProjectContribution;

use App\Balance\Application\ProjectContribution\Expire\ContributionExpirationScheduler;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;

class FakeContributionExpirationScheduler implements ContributionExpirationScheduler
{
    /** @var array<int, array{ProjectContributionId, \DateTimeImmutable}> */
    private array $scheduled = [];

    public function scheduleExpiration(ProjectContributionId $projectContributionId, \DateTimeImmutable $at): void
    {
        $this->scheduled[] = [$projectContributionId, $at];
    }

    /** @return array<int, array{ProjectContributionId, \DateTimeImmutable}> */
    public function scheduled(): array
    {
        return $this->scheduled;
    }
}
