<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\CreditPurchase\Balance;

use App\Balance\Application\CreditPurchase\Balance\CreditAvailability;
use App\Balance\Application\CreditPurchase\Balance\CreditSourceSelector;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Infrastructure\SharedCredit\DistributorSharedCreditProviders;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignment;
use App\Property\Domain\Model\Property\Property;
use App\Security\Domain\Model\Account\Account;
use App\Tests\Unit\Property\Application\DistributorAssignment\DistributedPropertiesPolicyMother;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;

class CreditSourceSelectorMother
{
    /**
     * @param list<Account>               $accounts
     * @param list<DistributorAssignment> $assignments
     * @param list<Property>              $properties
     */
    public static function create(
        AccountProjectRepository $accountProjects,
        CreditPurchaseRepository $creditPurchases,
        ProjectContributionRepository $contributions,
        array $accounts = [],
        array $assignments = [],
        array $properties = [],
    ): CreditSourceSelector {
        return new CreditSourceSelector(
            new CreditAvailability($creditPurchases, $contributions),
            new DistributorSharedCreditProviders(DistributedPropertiesPolicyMother::create($accounts, $properties, $assignments)),
            $accountProjects,
            AccountFeaturesPolicyMother::create($accounts),
        );
    }
}
