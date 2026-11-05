<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Property\List;

use App\Property\Application\DistributorAssignment\DistributedPropertiesPolicy;
use App\Property\Application\Property\List\DistributorPropertiesScope;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignment;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Application\Account\DistributorPolicy;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use PHPUnit\Framework\TestCase;

class DistributorPropertiesScopeTest extends TestCase
{
    public function testExpandsTheScopeOfAnActiveDistributor(): void
    {
        $filter = new PropertyFilter(accountId: new AccountId('distributor'), limit: 0);

        $this->createSUT()->expand($filter);

        $this->assertNull($filter->accountId);
        $this->assertNotNull($filter->scope);
        $this->assertContainsEquals(new AccountId('distributor'), $filter->scope->accountIds);
    }

    public function testLeavesTheFilterIntactForANonDistributorAccount(): void
    {
        $filter = new PropertyFilter(accountId: new AccountId('hotel'), limit: 0);

        $this->createSUT()->expand($filter);

        $this->assertEquals(new AccountId('hotel'), $filter->accountId);
        $this->assertNull($filter->scope);
    }

    public function testLeavesTheFilterIntactWithoutAccount(): void
    {
        $filter = new PropertyFilter(limit: 0);

        $this->createSUT()->expand($filter);

        $this->assertNull($filter->accountId);
        $this->assertNull($filter->scope);
    }

    public function testExpandedScopeMatchesOwnAndDistributedProperties(): void
    {
        $repository = new InMemoryPropertyRepository();
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $hotel = AccountMother::fromNameAndId('Hotel', 'hotel');
        $other = AccountMother::fromNameAndId('Other', 'other');

        $repository->add(PropertyMother::from(PropertyId::generate(), 'Own', $distributor->id()));
        $repository->add(PropertyMother::from(PropertyId::generate(), 'Distributed', $hotel->id()));
        $repository->add(PropertyMother::from(PropertyId::generate(), 'Foreign', $other->id()));

        $filter = new PropertyFilter(accountId: $distributor->id(), limit: 0);

        $this->createSUT($repository, [$distributor, $hotel, $other], [
            DistributorAssignmentMother::forAccount($distributor->id(), $hotel->id()),
        ])->expand($filter);

        $names = array_map(static fn($property): string => $property->name(), $repository->findByFilter($filter));
        sort($names);

        $this->assertSame(['Distributed', 'Own'], $names);
    }

    /**
     * @param list<Account>|null          $accounts
     * @param list<DistributorAssignment> $assignments
     */
    private function createSUT(?InMemoryPropertyRepository $repository = null, ?array $accounts = null, array $assignments = []): DistributorPropertiesScope
    {
        $repository ??= new InMemoryPropertyRepository();
        $accounts ??= [
            AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor'),
            AccountMother::fromNameAndId('Hotel', 'hotel'),
        ];

        $indexed = [];

        foreach ($accounts as $account) {
            $indexed[(string) $account->id()] = $account;
        }

        $accountRepository = new InMemoryAccountRepository($indexed);
        $distributorPolicy = new DistributorPolicy($accountRepository);

        return new DistributorPropertiesScope(
            new DistributedPropertiesPolicy(
                DistributorAssignmentRepositoryMother::inMemory($assignments),
                $repository,
                $distributorPolicy,
                new AccountFeaturesPolicy($accountRepository),
            ),
            $distributorPolicy,
        );
    }
}
