<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Property\List;

use App\Property\Application\DistributorAssignment\DistributedPropertiesPolicy;
use App\Property\Application\Property\List\DistributorPropertiesScope;
use App\Property\Application\Property\List\ListPropertiesRequest;
use App\Property\Application\Property\List\ListPropertiesService;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Application\Account\DistributorPolicy;
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

class ListPropertiesServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = PropertyRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $sut = $this->createSUT($repository);

        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::random());

        $request = new ListPropertiesRequest();

        $result = $sut($request, $user);

        $this->assertCount(4, $result->properties);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByOperatingRegime(): void
    {
        $repository = PropertyRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $sut = $this->createSUT($repository);

        $companyId = CompanyId::generate();

        $repository->add(PropertyMother::random());
        $repository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Franquicia A', $companyId, PropertyId::generate(), OperatingRegime::Franchised));
        $repository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Franquicia B', $companyId, PropertyId::generate(), OperatingRegime::Franchised));
        $repository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Arrendada', $companyId, PropertyId::generate(), OperatingRegime::LeasedOut));

        $result = $sut(new ListPropertiesRequest(operatingRegime: OperatingRegime::Franchised), $user);

        $this->assertCount(2, $result->properties);
        $this->assertEquals(2, $result->totalResults);
    }

    public function testDistributorSeesOwnAndDistributedPropertiesWithoutDuplicates(): void
    {
        $sut = $this->createDistributorSUT(PropertyRepositoryMother::inMemory(), new AccountId('distributor'));

        $result = $sut(new ListPropertiesRequest(accountId: new AccountId('distributor'), limit: 0), $this->owner('distributor'));

        $this->assertSame(['By account', 'By account and property', 'By property', 'Own'], $this->sortedNames($result->properties));
        $this->assertSame(4, $result->totalResults);
    }

    public function testDistributorRequestIsNotMutated(): void
    {
        $sut = $this->createDistributorSUT(PropertyRepositoryMother::inMemory(), new AccountId('distributor'));

        $request = new ListPropertiesRequest(accountId: new AccountId('distributor'), limit: 0);

        $sut($request, $this->owner('distributor'));

        $this->assertEquals(new AccountId('distributor'), $request->accountId);
        $this->assertNull($request->scope);

        $result = $sut($request, $this->owner('distributor'));

        $this->assertSame(4, $result->totalResults);
    }

    public function testNonDistributorSeesOnlyOwnProperties(): void
    {
        $sut = $this->createDistributorSUT(PropertyRepositoryMother::inMemory(), new AccountId('hotel'));

        $result = $sut(new ListPropertiesRequest(accountId: new AccountId('hotel'), limit: 0), $this->owner('hotel'));

        $this->assertSame(['By account', 'By account and property'], $this->sortedNames($result->properties));
    }

    public function testForeignAccountIdIsNotExpanded(): void
    {
        $sut = $this->createDistributorSUT(PropertyRepositoryMother::inMemory(), new AccountId('distributor'));

        $result = $sut(new ListPropertiesRequest(accountId: new AccountId('distributor'), limit: 0), UserMother::adminUser());

        $this->assertSame(['Own'], $this->sortedNames($result->properties));
    }

    private function owner(string $accountId): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId($accountId));
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

    private function createDistributorSUT(InMemoryPropertyRepository $repository, AccountId $currentAccountId): ListPropertiesService
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $hotel = AccountMother::fromNameAndId('Hotel', 'hotel');
        $disabled = AccountMother::withFeatures('Disabled', 'disabled', new AccountFeatures(propertyTripCalculators: false));
        $other = AccountMother::fromNameAndId('Other', 'other');

        $both = PropertyMother::from(PropertyId::generate(), 'By account and property', $hotel->id());
        $byProperty = PropertyMother::from(PropertyId::generate(), 'By property', $other->id());

        $repository->add(PropertyMother::from(PropertyId::generate(), 'Own', $distributor->id()));
        $repository->add(PropertyMother::from(PropertyId::generate(), 'By account', $hotel->id()));
        $repository->add($both);
        $repository->add($byProperty);
        $repository->add(PropertyMother::from(PropertyId::generate(), 'Disabled hotel', $disabled->id()));
        $repository->add(PropertyMother::from(PropertyId::generate(), 'Not assigned', $other->id()));

        $accountRepository = new InMemoryAccountRepository([
            (string) $distributor->id() => $distributor,
            (string) $hotel->id() => $hotel,
            (string) $disabled->id() => $disabled,
            (string) $other->id() => $other,
        ]);
        $distributorPolicy = new DistributorPolicy($accountRepository);
        $accountFeaturesPolicy = new AccountFeaturesPolicy($accountRepository);

        $distributedPropertiesPolicy = new DistributedPropertiesPolicy(
            DistributorAssignmentRepositoryMother::inMemory([
                DistributorAssignmentMother::forAccount($distributor->id(), $hotel->id()),
                DistributorAssignmentMother::forAccount($distributor->id(), $disabled->id()),
                DistributorAssignmentMother::forProperty($distributor->id(), $both->id()),
                DistributorAssignmentMother::forProperty($distributor->id(), $byProperty->id()),
            ]),
            $repository,
            $distributorPolicy,
            $accountFeaturesPolicy,
        );

        $authorizer = new PropertyAuthorizer(
            currentAccountIdProvider: new StubCurrentAccountIdProvider($currentAccountId),
            accountFeaturesPolicy: $accountFeaturesPolicy,
            distributedPropertiesPolicy: $distributedPropertiesPolicy,
            distributorPolicy: $distributorPolicy,
        );

        return new ListPropertiesService($repository, $authorizer, new DistributorPropertiesScope($distributedPropertiesPolicy, $distributorPolicy));
    }

    private function createSUT(InMemoryPropertyRepository $repository): ListPropertiesService
    {
        return new ListPropertiesService($repository, new PropertyAuthorizer());
    }
}
