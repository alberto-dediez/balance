<?php

declare(strict_types=1);

namespace App\Balance\Application\ProjectContribution\Reallocate;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

final class ReallocateAllUnderallocatedContributionsRequest implements Command
{
    public function __construct(
        public readonly ?AccountId $accountId = null,
        public readonly ?ProjectId $projectId = null,
    ) {}
}
