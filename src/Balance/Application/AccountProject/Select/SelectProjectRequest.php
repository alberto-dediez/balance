<?php

declare(strict_types=1);

namespace App\Balance\Application\AccountProject\Select;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class SelectProjectRequest implements Command
{
    public function __construct(
        public AccountId $accountId,
        public ProjectId $projectId,
    ) {}
}
