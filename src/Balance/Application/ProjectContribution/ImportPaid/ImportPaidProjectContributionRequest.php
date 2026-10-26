<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\ImportPaid;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

final class ImportPaidProjectContributionRequest implements Command
{
    public function __construct(
        public readonly ProjectContributionId $projectContributionId,
        public readonly AccountId $accountId,
        public readonly ProjectId $projectId,
        public readonly float $amount,
        public readonly string $contributorName,
        public readonly string $contributorEmail,
        public readonly \DateTimeImmutable $paidAt,
    ) {}
}
