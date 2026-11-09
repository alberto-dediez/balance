<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\CreditPurchase;

use App\Security\Domain\Model\Account\AccountId;

class CompensationNotEnabledForAccountException extends \DomainException
{
    public function __construct(private AccountId $accountId)
    {
        parent::__construct(sprintf('Account %s has not the compensation feature enabled', (string) $accountId));
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }
}
