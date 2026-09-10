<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Transfer;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class TransferCreditPurchaseRequest implements Command
{
    public function __construct(
        public CreditPurchaseId $creditPurchaseId,
        public ?AccountId $destinationAccountId = null,
        public ?float $tonnes = null,
    ) {}
}
