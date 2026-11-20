<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ContactRequest;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class ContactRequestFilter
{
    use Filter;

    public function __construct(
        public ?bool $resolved = null,
        public ?AccountId $accountId = null,
        public ?string $search = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
