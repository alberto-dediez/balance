<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Balance;

use App\Security\Domain\Model\Account\AccountId;

class ListAccountCreditsRequest
{
    public function __construct(
        public AccountId $accountId,
    ) {}
}
