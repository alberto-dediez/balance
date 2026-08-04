<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Validate;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Library\Domain\Model\Media\MediaId;
use App\Shared\Application\CommandBus\Command;

class ValidateCreditPurchaseRequest implements Command
{
    public function __construct(
        public CreditPurchaseId $creditPurchaseId,
        public float $purchasedTonnes,
        public float $amount,
        public string $reference,
        public ?MediaId $certificate = null,
    ) {}
}
