<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;

interface SharedCreditProviders
{
    /** @return list<AccountId> */
    public function for(AccountId $accountId, ?PropertyId $propertyId): array;
}
