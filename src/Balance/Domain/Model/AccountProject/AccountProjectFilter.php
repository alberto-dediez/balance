<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\AccountProject;

use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class AccountProjectFilter
{
    use Filter;

    public function __construct(
        public ?AccountId $accountId = null,
        public ?ProjectId $projectId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
