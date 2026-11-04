<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ProjectContribution;

use App\Balance\Application\ProjectContribution\ProjectContributionAuthorizer;
use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Application\Account\DistributorPolicy;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\CarbonFootprint\Infrastructure\Contribution\PropertyTripDistributedContributionsResolverMother;
use App\Tests\Unit\Property\Domain\Model\DistributorAssignment\DistributorAssignmentMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

class ProjectContributionAuthorizerTest extends TestCase
{
    public function testAdminCanListViewAndEditButNeverRegisterOrRemove(): void
    {
        $user = UserMother::adminUser();
        $subject = $this->subject(AccountId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::View, $user));
        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Register, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerReadsTheContributionsOfOwnAccountOnly(): void
    {
        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $this->assertTrue($this->createSUT()->canDoThat($this->subject($accountId), Action::List, $user));
        $this->assertTrue($this->createSUT()->canDoThat($this->subject($accountId), Action::View, $user));
        $this->assertFalse($this->createSUT()->canDoThat($this->subject($accountId), Action::Edit, $user));
    }

    public function testOnlyTheOwnerOfTheAccountCanRegisterAContribution(): void
    {
        $accountId = AccountId::generate();
        $subject = $this->subject($accountId);

        $this->assertTrue($this->createSUT()->canDoThat(
            $subject,
            Action::Register,
            UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId),
        ));
        $this->assertFalse($this->createSUT()->canDoThat(
            $subject,
            Action::Register,
            UserMother::fromUsernameAndRoleAndAccount('other', Role::Owner, AccountId::generate()),
        ));
        $this->assertFalse($this->createSUT()->canDoThat(
            $subject,
            Action::Register,
            UserMother::fromUsernameAndRoleAndAccount('user', Role::User, $accountId),
        ));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Register, UserMother::adminUser()));
        $this->assertFalse($this->createSUT()->canDoThat(
            $subject,
            Action::Register,
            UserMother::fromUsernameAndRole('super', Role::SuperAdmin),
        ));
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($this->subject(AccountId::generate()), Action::List, $user));
        $this->assertFalse($this->createSUT()->canDoThat($this->subject(null), Action::List, $user));
    }

    public function testUserWithoutAccountIsDenied(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);

        $this->assertFalse($this->createSUT()->canDoThat($this->subject(AccountId::generate()), Action::List, $user));
    }

    public function testSupportsOnlyProjectContributions(): void
    {
        $this->assertTrue($this->createSUT()->supports($this->subject(null)));
        $this->assertFalse($this->createSUT()->supports(ProjectContributionId::generate()));
    }

    public function testOwnerCannotRegisterNorReadContributionsWithoutCompensationFeature(): void
    {
        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $sut = $this->createSUTWithFeatures($accountId, new AccountFeatures(compensation: false));

        $this->assertFalse($sut->canDoThat($this->subject($accountId), Action::Register, $user));
        $this->assertFalse($sut->canDoThat($this->subject($accountId), Action::List, $user));
        $this->assertFalse($sut->canDoThat($this->subject($accountId), Action::View, $user));
    }

    public function testAdminCanListAndEditContributionsWithCompensationFeatureDisabled(): void
    {
        $accountId = AccountId::generate();
        $sut = $this->createSUTWithFeatures($accountId, new AccountFeatures(compensation: false));

        $this->assertTrue($sut->canDoThat($this->subject($accountId), Action::List, UserMother::adminUser()));
        $this->assertTrue($sut->canDoThat($this->subject($accountId), Action::Edit, UserMother::adminUser()));
    }

    public function testDistributorOwnerListsOwnAccountWithoutCompensationFeature(): void
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $distributor->changeFeatures(new AccountFeatures(compensation: false));
        $sut = $this->createDistributorSUT($distributor->id(), [$distributor]);

        $this->assertTrue($sut->canDoThat($this->subject($distributor->id()), Action::List, UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $distributor->id())));
        $this->assertFalse($sut->canDoThat($this->subject($distributor->id()), Action::List, UserMother::fromUsernameAndRoleAndAccount('user', Role::User, $distributor->id())));
        $this->assertFalse($sut->canDoThat($this->subject($distributor->id()), Action::Register, UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $distributor->id())));
    }

    public function testDistributorOwnerViewsContributionsOfDistributedPropertiesOnly(): void
    {
        $distributor = AccountMother::distributorAccountFromNameAndId('Greenhost', 'distributor');
        $hotel = AccountMother::fromNameAndId('Hotel', 'hotel');
        $property = PropertyMother::from(PropertyId::generate(), 'Hotel 1', $hotel->id());
        $sut = $this->createDistributorSUT($distributor->id(), [$distributor, $hotel], [$property]);
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $distributor->id());

        $distributed = ProjectContributionMother::pending($hotel->id(), originId: (string) $property->id(), origin: ProjectContributionOrigin::Property);
        $notDistributed = ProjectContributionMother::pending(AccountId::generate(), origin: ProjectContributionOrigin::Property);

        $this->assertTrue($sut->canDoThat($distributed, Action::View, $owner));
        $this->assertFalse($sut->canDoThat($distributed, Action::List, $owner));
        $this->assertFalse($sut->canDoThat($distributed, Action::Edit, $owner));
        $this->assertFalse($sut->canDoThat($notDistributed, Action::View, $owner));
        $this->assertFalse($sut->canDoThat($distributed, Action::View, UserMother::fromUsernameAndRoleAndAccount('user', Role::User, $distributor->id())));
    }

    private function subject(?AccountId $accountId): ProjectContribution
    {
        return new ProjectContribution(id: ProjectContributionId::generate(), accountId: $accountId);
    }

    private function createSUT(): ProjectContributionAuthorizer
    {
        return new ProjectContributionAuthorizer();
    }

    /**
     * @param list<Account>  $accounts
     * @param list<Property> $properties
     */
    private function createDistributorSUT(AccountId $currentAccountId, array $accounts, array $properties = []): ProjectContributionAuthorizer
    {
        $indexedAccounts = [];

        foreach ($accounts as $account) {
            $indexedAccounts[(string) $account->id()] = $account;
        }

        $accountRepository = new InMemoryAccountRepository($indexedAccounts);

        return new ProjectContributionAuthorizer(
            currentAccountIdProvider: new StubCurrentAccountIdProvider($currentAccountId),
            accountFeaturesPolicy: new AccountFeaturesPolicy($accountRepository),
            distributorPolicy: new DistributorPolicy($accountRepository),
            distributedContributionsResolver: PropertyTripDistributedContributionsResolverMother::create(
                $accounts,
                $properties,
                array_map(
                    static fn(Account $account) => DistributorAssignmentMother::forAccount($currentAccountId, $account->id()),
                    array_values(array_filter($accounts, static fn(Account $account): bool => !$account->id()->equals($currentAccountId))),
                ),
            ),
        );
    }

    private function createSUTWithFeatures(AccountId $accountId, AccountFeatures $features): ProjectContributionAuthorizer
    {
        return new ProjectContributionAuthorizer(
            currentAccountIdProvider: new StubCurrentAccountIdProvider($accountId),
            accountFeaturesPolicy: AccountFeaturesPolicyMother::forAccount($accountId, $features),
        );
    }
}
