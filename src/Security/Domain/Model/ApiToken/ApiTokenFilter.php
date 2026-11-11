<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\ApiToken;

use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\Filter;

class ApiTokenFilter
{
    use Filter;

    public function __construct(
        public ?UserId $userId = null,
        public ?ApiTokenStatus $status = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
