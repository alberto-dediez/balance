<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Validate;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseId;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Library\Domain\Model\Media\MediaId;
use App\Shared\Application\CommandBus\Command;

class ValidateCreditPurchaseRequest implements Command
{
    public function __construct(
        public CreditPurchaseId $creditPurchaseId,
        public float $purchasedTonnes,
        public string $reference,
        public ?CreditPurchasePaymentType $paymentType = null,
        public ?MediaId $certificate = null,
    ) {}
}
