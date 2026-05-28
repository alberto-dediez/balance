<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\SignatureRequest;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class SignatureRequestFilter
{
    use Filter;

    public function __construct(
        public ?AccountId $accountId = null,
        public ?SignatureRequestStatus $status = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
