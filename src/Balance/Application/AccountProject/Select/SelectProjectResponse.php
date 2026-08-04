<?php

declare(strict_types=1);

namespace App\Balance\Application\AccountProject\Select;

use App\Balance\Domain\Model\AccountProject\AccountProjectId;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\CommandResponse;

class SelectProjectResponse implements CommandResponse
{
    public function __construct(
        public AccountProjectId $accountProjectId,
        public AccountId $accountId,
        public ProjectId $projectId,
    ) {}
}
