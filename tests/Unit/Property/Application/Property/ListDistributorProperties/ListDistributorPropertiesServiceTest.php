<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Property\ListDistributorProperties;

use App\Property\Application\DistributorAssignment\DistributedPropertiesPolicy;
use App\Property\Application\Property\List\DistributorPropertiesScope;
use App\Property\Application\Property\ListDistributorProperties\ListDistributorPropertiesRequest;
use App\Property\Application\Property\ListDistributorProperties\ListDistributorPropertiesResponse;
use App\Property\Application\Property\ListDistributorProperties\ListDistributorPropertiesService;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\DistributorAssignment\AccountIsNotDistributorException;
use App\Property\Domain\Model\DistributorAssignment\DistributorAssignment;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Application\Account\DistributorPolicy;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

class ListDistributorPropertiesServiceTest extends TestCase
{
    private InMemoryPropertyRepository $repository;

    private Account $distributor;

    private Account $hotel;

    private Account $disabled;

    private Account $other;

    private Account $otherDistributor;

    /** @var list<DistributorAssignment> */
    private array $assignments = [];

    protected function setUp(): void
    {
        $this->repository = PropertyRepositoryMother::inMemory();
        $this->distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $this->hotel = AccountMother::fromNameAndId('Hotel', 'hotel');
        $this->disabled = AccountMother::withFeatures('Disabled', 'disabled', new AccountFeatures(propertyTripCalculators: false));
        $this->other = AccountMother::fromNameAndId('Other', 'other');
        $this->otherDistributor = AccountMother::distributorAccountFromNameAndId('Agencia', 'other-distributor');
        $this->assignments = [];
    }

    public function testAdminListsOwnAndDistributedProperties(): void
    {
        $this->addProperty('Own', $this->distributor);
        $this->addProperty('Hotel one', $this->hotel);
        $this->addProperty('Hotel two', $this->hotel);
        $byProperty = $this->addProperty('By property', $this->other);
        $this->assignAccount($this->hotel);
        $this->assignProperty($byProperty);

        $result = $this->list(UserMother::adminUser());

        $this->assertSame(['By property', 'Hotel one', 'Hotel two', 'Own'], $this->sortedNames($result->properties));
        $this->assertSame(4, $result->totalResults);
    }

    public function testPropertyAssignedByAccountAndByPropertyIsNotDuplicated(): void
    {
        $both = $this->addProperty('Both', $this->hotel);
        $this->assignAccount($this->hotel);
        $this->assignProperty($both);

        $result = $this->list(UserMother::adminUser());

        $this->assertSame(['Both'], $this->sortedNames($result->properties));
        $this->assertSame(1, $result->totalResults);
    }

    public function testNotAssignedPropertyIsExcluded(): void
    {
        $this->addProperty('Own', $this->distributor);
        $this->addProperty('Not assigned', $this->other);

        $result = $this->list(UserMother::adminUser());

        $this->assertSame(['Own'], $this->sortedNames($result->properties));
    }

    public function testHotelWithoutPropertyTripCalculatorsIsExcluded(): void
    {
        $this->addProperty('Own', $this->distributor);
        $byProperty = $this->addProperty('Disabled by property', $this->disabled);
        $this->addProperty('Disabled by account', $this->disabled);
        $this->assignAccount($this->disabled);
        $this->assignProperty($byProperty);

        $result = $this->list(UserMother::adminUser());

        $this->assertSame(['Own'], $this->sortedNames($result->properties));
    }

    public function testOwnPropertiesAreListedRegardlessOfFeatures(): void
    {
        $this->distributor = AccountMother::withFeatures('Greenhost', 'distributor', new AccountFeatures(propertyTripCalculators: false));
        $this->distributor->changeDistributorStatus(true);
        $this->addProperty('Own', $this->distributor);

        $result = $this->list(UserMother::adminUser());

        $this->assertSame(['Own'], $this->sortedNames($result->properties));
    }

    public function testRemovedPropertyIsExcluded(): void
    {
        $this->addProperty('Own', $this->distributor);
        $removed = $this->addProperty('Removed', $this->hotel);
        $removed->remove();
        $this->assignAccount($this->hotel);

        $result = $this->list(UserMother::adminUser());

        $this->assertSame(['Own'], $this->sortedNames($result->properties));
        $this->assertSame(1, $result->totalResults);
    }

    public function testDistributorWithoutPropertiesReturnsEmptyList(): void
    {
        $this->addProperty('Not assigned', $this->other);

        $result = $this->list(UserMother::adminUser());

        $this->assertSame([], $result->properties);
        $this->assertSame(0, $result->totalResults);
    }

    public function testReturnsMoreThanTenProperties(): void
    {
        for ($i = 1; $i <= 6; ++$i) {
            $this->addProperty('Own ' . $i, $this->distributor);
            $this->addProperty('Hotel ' . $i, $this->hotel);
        }
        $this->assignAccount($this->hotel);

        $result = $this->list(UserMother::adminUser());

        $this->assertCount(12, $result->properties);
        $this->assertSame(12, $result->totalResults);
    }

    public function testDistributorOwnerListsItsOwnDistributor(): void
    {
        $this->addProperty('Own', $this->distributor);
        $this->addProperty('Hotel one', $this->hotel);
        $this->assignAccount($this->hotel);

        $result = $this->list($this->user(Role::Owner, $this->distributor->id()), $this->distributor->id());

        $this->assertSame(['Hotel one', 'Own'], $this->sortedNames($result->properties));
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->list($this->user(Role::Owner, $this->hotel->id()), $this->hotel->id());
    }

    public function testOwnerOfAnotherDistributorIsDenied(): void
    {
        $this->addProperty('Own', $this->distributor);

        $this->expectException(UnauthorizedActionException::class);

        $this->list($this->user(Role::Owner, $this->otherDistributor->id()), $this->otherDistributor->id());
    }

    public function testUserWithoutOwnerRoleIsDenied(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $this->list($this->user(Role::User, $this->distributor->id()), $this->distributor->id());
    }

    public function testNonDistributorAccountThrows(): void
    {
        $this->expectException(AccountIsNotDistributorException::class);

        $this->list(UserMother::adminUser(), null, $this->hotel->id());
    }

    public function testUnknownAccountThrows(): void
    {
        $this->expectException(AccountIsNotDistributorException::class);

        $this->list(UserMother::adminUser(), null, new AccountId('unknown'));
    }

    public function testInactiveDistributorThrows(): void
    {
        $this->distributor = AccountMother::inactiveDistributorAccountFromNameAndId('Greenhost', 'distributor');

        $this->expectException(AccountIsNotDistributorException::class);

        $this->list(UserMother::adminUser());
    }

    private function addProperty(string $name, Account $account): Property
    {
        $property = PropertyMother::from(PropertyId::generate(), $name, $account->id());
        $this->repository->add($property);

        return $property;
    }

    private function assignAccount(Account $account): void
    {
        $this->assignments[] = DistributorAssignmentMother::forAccount($this->distributor->id(), $account->id());
    }

    private function assignProperty(Property $property): void
    {
        $this->assignments[] = DistributorAssignmentMother::forProperty($this->distributor->id(), $property->id());
    }

    private function user(Role $role, AccountId $accountId): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('user', $role, $accountId);
    }

    private function list(User $user, ?AccountId $currentAccountId = null, ?AccountId $distributorId = null): ListDistributorPropertiesResponse
    {
        return $this->createSUT($currentAccountId)(new ListDistributorPropertiesRequest($distributorId ?? $this->distributor->id()), $user);
    }

    /**
     * @param array<Property> $properties
     *
     * @return list<string>
     */
    private function sortedNames(array $properties): array
    {
        $names = array_map(static fn(Property $property): string => $property->name(), $properties);
        sort($names);

        return $names;
    }

    private function createSUT(?AccountId $currentAccountId): ListDistributorPropertiesService
    {
        $accountRepository = new InMemoryAccountRepository([
            (string) $this->distributor->id() => $this->distributor,
            (string) $this->hotel->id() => $this->hotel,
            (string) $this->disabled->id() => $this->disabled,
            (string) $this->other->id() => $this->other,
            (string) $this->otherDistributor->id() => $this->otherDistributor,
        ]);
        $distributorPolicy = new DistributorPolicy($accountRepository);
        $accountFeaturesPolicy = new AccountFeaturesPolicy($accountRepository);

        $distributedPropertiesPolicy = new DistributedPropertiesPolicy(
            DistributorAssignmentRepositoryMother::inMemory($this->assignments),
            $this->repository,
            $distributorPolicy,
            $accountFeaturesPolicy,
        );

        $authorizer = new PropertyAuthorizer(
            currentAccountIdProvider: new StubCurrentAccountIdProvider($currentAccountId),
            accountFeaturesPolicy: $accountFeaturesPolicy,
            distributedPropertiesPolicy: $distributedPropertiesPolicy,
            distributorPolicy: $distributorPolicy,
        );

        return new ListDistributorPropertiesService(
            $this->repository,
            $authorizer,
            $distributorPolicy,
            new DistributorPropertiesScope($distributedPropertiesPolicy, $distributorPolicy),
        );
    }
}
