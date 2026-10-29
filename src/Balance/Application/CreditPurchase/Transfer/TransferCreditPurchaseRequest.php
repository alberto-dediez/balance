<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Transfer;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class TransferCreditPurchaseRequest implements Command
{
    /** @param list<AccountId> $destinationAccountIds */
    public function __construct(
        public CreditPurchaseId $creditPurchaseId,
        public array $destinationAccountIds = [],
        public ?float $tonnes = null,
    ) {}
}
