<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\SharedCredit;

use App\Balance\Domain\Model\CreditPurchase\SharedCreditProviders;
use App\Property\Application\DistributorAssignment\DistributedPropertiesPolicy;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;

final readonly class DistributorSharedCreditProviders implements SharedCreditProviders
{
    public function __construct(
        private DistributedPropertiesPolicy $distributedPropertiesPolicy,
    ) {}

    /** @return list<AccountId> */
    public function for(AccountId $accountId, ?PropertyId $propertyId): array
    {
        return $this->distributedPropertiesPolicy->distributorsOf($accountId, $propertyId);
    }
}
