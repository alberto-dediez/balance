<?php

declare(strict_types=1);

namespace App\Balance\Application\CreditPurchase\Assign;

use App\Balance\Domain\Model\CreditPurchase\CreditPurchasePaymentType;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Library\Domain\Model\Media\MediaId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CommandBus\Command;

class AssignCreditPurchaseRequest implements Command
{
    public function __construct(
        public ?AccountId $accountId = null,
        public ?ProjectId $projectId = null,
        public ?float $purchasedTonnes = null,
        public string $reference = '',
        public ?CreditPurchasePaymentType $paymentType = CreditPurchasePaymentType::PaidByBioscore,
        public ?float $amount = null,
        public ?MediaId $certificate = null,
    ) {}
}
