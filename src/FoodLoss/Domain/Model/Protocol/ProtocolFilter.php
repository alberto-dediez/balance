<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Protocol;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Filter;

class ProtocolFilter
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
