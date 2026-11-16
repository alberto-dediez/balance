<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Balance;

use App\Security\Domain\Model\Account\AccountId;

final readonly class CreditSource
{
    public function __construct(
        public AccountId $accountId,
        public float $availableTonnes,
        public bool $shared,
    ) {}
}
