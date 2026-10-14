<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Company;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class CompanyFilter
{
    use Filter;

    public function __construct(
        public ?string $name = null,
        public ?AccountId $accountId = null,
        int $limit = 10,
    ) {
        $this->limit = $limit;
    }
}
