<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\DistributorAssignment;

use App\Property\Application\DistributorAssignment\DistributedPropertiesPolicy;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignment;
use App\Property\Domain\Model\Property\Property;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Application\Account\DistributorPolicy;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepositoryMother;

class DistributedPropertiesPolicyMother
{
    /**
     * @param list<Account>               $accounts
     * @param list<Property>              $properties
     * @param list<DistributorAssignment> $assignments
     */
    public static function create(array $accounts = [], array $properties = [], array $assignments = []): DistributedPropertiesPolicy
    {
        $indexedAccounts = [];

        foreach ($accounts as $account) {
            $indexedAccounts[(string) $account->id()] = $account;
        }

        $accountRepository = new InMemoryAccountRepository($indexedAccounts);

        $propertyRepository = new InMemoryPropertyRepository();

        foreach ($properties as $property) {
            $propertyRepository->add($property);
        }

        return new DistributedPropertiesPolicy(
            DistributorAssignmentRepositoryMother::inMemory($assignments),
            $propertyRepository,
            new DistributorPolicy($accountRepository),
            new AccountFeaturesPolicy($accountRepository),
        );
    }
}
