<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Expire;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Shared\Application\CommandBus\Command;

final class ExpireProjectContributionRequest implements Command
{
    public function __construct(
        public readonly ProjectContributionId $projectContributionId,
        public readonly ?\DateTimeImmutable $now = null,
    ) {}
}
