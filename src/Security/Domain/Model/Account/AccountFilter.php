<?php

namespace App\Security\Domain\Model\Account;

use App\Shared\Domain\Model\Filter;

class AccountFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?bool $active = null,
        int $limit = 10
    ) {
        $this->limit = $limit;
    }
}