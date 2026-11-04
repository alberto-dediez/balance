<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ProjectContribution\List;

use App\Balance\Application\ProjectContribution\List\ListProjectContributionsRequest;
use App\Balance\Application\ProjectContribution\List\ListProjectContributionsService;
use App\Balance\Application\ProjectContribution\ProjectContributionAuthorizer;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Application\Account\DistributorPolicy;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Infrastructure\Contribution\PropertyTripDistributedContributionsResolverMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

class ListProjectContributionsServiceTest extends TestCase
{
    public function testAdminListsAllContributions(): void
    {
        $repository = ProjectContributionRepositoryMother::inMemory();
        $repository->add(ProjectContributionMother::random());
        $repository->add(ProjectContributionMother::random());

        $result = $this->createSUT($repository)(
            new ListProjectContributionsRequest(limit: 0),
            UserMother::adminUser(),
        );

        $this->assertSame(2, $result->totalResults);
        $this->assertCount(2, $result->projectContributions);
    }

    public function testTheStatusFilterIsApplied(): void
    {
        $repository = ProjectContributionRepositoryMother::inMemory();
        $repository->add(ProjectContributionMother::paid());
        $repository->add(ProjectContributionMother::pending());

        $result = $this->createSUT($repository)(
            new ListProjectContributionsRequest(status: ProjectContributionStatus::Paid, limit: 0),
            UserMother::adminUser(),
        );

        $this->assertSame(1, $result->totalResults);
    }

    public function testOwnerListsOwnAccountContributionsOnly(): void
    {
        $accountId = AccountId::generate();

        $repository = ProjectContributionRepositoryMother::inMemory();
        $repository->add(ProjectContributionMother::pending($accountId));
        $repository->add(ProjectContributionMother::random());

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $result = $this->createSUT($repository)(
            new ListProjectContributionsRequest(accountId: $accountId, limit: 0),
            $user,
        );

        $this->assertSame(1, $result->totalResults);
    }

    public function testThrowsWhenOwnerRequestsAnotherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()(new ListProjectContributionsRequest(accountId: AccountId::generate(), limit: 0), $user);
    }

    public function testThrowsWhenOwnerRequestsEveryAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()(new ListProjectContributionsRequest(limit: 0), $user);
    }

    public function testDistributorOwnerListsOwnAndDistributedPropertyContributions(): void
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $hotel = AccountMother::fromNameAndId('Hotel', 'hotel');

        $repository = ProjectContributionRepositoryMother::inMemory();
        $repository->add(ProjectContributionMother::pending($distributor->id(), origin: ProjectContributionOrigin::EventCarbonFootprint));
        $repository->add(ProjectContributionMother::pending($hotel->id()));
        $repository->add(ProjectContributionMother::pending($hotel->id(), origin: ProjectContributionOrigin::Property));
        $repository->add(ProjectContributionMother::pending($hotel->id(), origin: ProjectContributionOrigin::EventCarbonFootprint));
        $repository->add(ProjectContributionMother::random());

        $result = $this->createDistributorSUT($repository, $distributor, $hotel)(
            new ListProjectContributionsRequest(accountId: $distributor->id(), limit: 0),
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $distributor->id()),
        );

        $this->assertSame(3, $result->totalResults);
    }

    public function testDistributorUserOnlyListsOwnAccountContributions(): void
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $hotel = AccountMother::fromNameAndId('Hotel', 'hotel');

        $repository = ProjectContributionRepositoryMother::inMemory();
        $repository->add(ProjectContributionMother::pending($distributor->id()));
        $repository->add(ProjectContributionMother::pending($hotel->id()));

        $result = $this->createDistributorSUT($repository, $distributor, $hotel)(
            new ListProjectContributionsRequest(accountId: $distributor->id(), limit: 0),
            UserMother::fromUsernameAndRoleAndAccount('user', Role::User, $distributor->id()),
        );

        $this->assertSame(1, $result->totalResults);
    }

    public function testDistributorOwnerFiltersByDistributedProperty(): void
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $hotel = AccountMother::fromNameAndId('Hotel', 'hotel');
        $property = PropertyMother::from(PropertyId::generate(), 'Hotel 1', $hotel->id());
        $otherProperty = PropertyMother::from(PropertyId::generate(), 'Hotel 2', $hotel->id());

        $repository = ProjectContributionRepositoryMother::inMemory();
        $repository->add(ProjectContributionMother::pending($hotel->id(), originId: (string) $property->id(), origin: ProjectContributionOrigin::Property));
        $repository->add(ProjectContributionMother::pending($hotel->id(), originId: (string) $otherProperty->id(), origin: ProjectContributionOrigin::Property));
        $repository->add(ProjectContributionMother::pending($distributor->id()));

        $request = new ListProjectContributionsRequest(accountId: $distributor->id(), limit: 0);
        $request->propertyId = $property->id();

        $result = $this->createDistributorSUT($repository, $distributor, $hotel, [$property, $otherProperty])(
            $request,
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $distributor->id()),
        );

        $this->assertSame(1, $result->totalResults);
    }

    public function testFilteringByAForeignPropertyReturnsNothing(): void
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $hotel = AccountMother::fromNameAndId('Hotel', 'hotel');
        $foreignAccountId = AccountId::generate();
        $foreign = PropertyMother::from(PropertyId::generate(), 'Foreign', $foreignAccountId);

        $repository = ProjectContributionRepositoryMother::inMemory();
        $repository->add(ProjectContributionMother::pending($foreignAccountId, originId: (string) $foreign->id(), origin: ProjectContributionOrigin::Property));

        $request = new ListProjectContributionsRequest(accountId: $distributor->id(), limit: 0);
        $request->propertyId = $foreign->id();

        $result = $this->createDistributorSUT($repository, $distributor, $hotel, [$foreign])(
            $request,
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $distributor->id()),
        );

        $this->assertSame(0, $result->totalResults);
    }

    /** @param list<Property> $properties */
    private function createDistributorSUT(
        ProjectContributionRepository $repository,
        Account $distributor,
        Account $hotel,
        array $properties = [],
    ): ListProjectContributionsService {
        $accountRepository = new InMemoryAccountRepository([
            (string) $distributor->id() => $distributor,
            (string) $hotel->id() => $hotel,
        ]);

        $resolver = PropertyTripDistributedContributionsResolverMother::create(
            [$distributor, $hotel],
            $properties,
            [DistributorAssignmentMother::forAccount($distributor->id(), $hotel->id())],
        );

        return new ListProjectContributionsService(
            $repository,
            new ProjectContributionAuthorizer(
                currentAccountIdProvider: new StubCurrentAccountIdProvider($distributor->id()),
                accountFeaturesPolicy: new AccountFeaturesPolicy($accountRepository),
                distributorPolicy: new DistributorPolicy($accountRepository),
                distributedContributionsResolver: $resolver,
            ),
            $resolver,
        );
    }

    private function createSUT(?ProjectContributionRepository $repository = null): ListProjectContributionsService
    {
        return new ListProjectContributionsService(
            $repository ?? ProjectContributionRepositoryMother::inMemory(),
            new ProjectContributionAuthorizer(),
        );
    }
}
