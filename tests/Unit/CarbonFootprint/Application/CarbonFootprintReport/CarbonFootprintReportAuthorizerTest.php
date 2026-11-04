<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use App\Tests\Unit\Shared\Application\CurrentRoles\StubCurrentRolesProvider;
use PHPUnit\Framework\TestCase;

class CarbonFootprintReportAuthorizerTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testOwnerOfTheAccountCanViewEditAndRemoveButNotAdmin(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = CarbonFootprintReportMother::registeredFor(accountId: new AccountId('account-id-test'));
        $sut = new CarbonFootprintReportAuthorizer();

        $this->assertTrue($sut->canDoThat($subject, Action::View, $user));
        $this->assertTrue($sut->canDoThat($subject, Action::Edit, $user));
        $this->assertTrue($sut->canDoThat($subject, Action::Remove, $user));
        $this->assertFalse($sut->canDoThat($subject, Action::Admin, $user));
    }

    public function testOwnerOfAnotherAccountCannotViewNorEdit(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = CarbonFootprintReportMother::registeredFor(accountId: new AccountId('other-account-id'));
        $sut = new CarbonFootprintReportAuthorizer();

        $this->assertFalse($sut->canDoThat($subject, Action::View, $user));
        $this->assertFalse($sut->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCanViewRemoveAndAdminButNotEdit(): void
    {
        $user = UserMother::adminUser();
        $subject = CarbonFootprintReportMother::registeredFor(accountId: new AccountId('account-id-test'));
        $sut = new CarbonFootprintReportAuthorizer();

        $this->assertTrue($sut->canDoThat($subject, Action::View, $user));
        $this->assertTrue($sut->canDoThat($subject, Action::Admin, $user));
        $this->assertTrue($sut->canDoThat($subject, Action::Remove, $user));
        $this->assertFalse($sut->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminImpersonatingTheOwnerCanEdit(): void
    {
        $user = UserMother::adminUser();
        $subject = CarbonFootprintReportMother::registeredFor(accountId: new AccountId('account-id-test'));
        $sut = new CarbonFootprintReportAuthorizer(
            new StubCurrentAccountIdProvider(new AccountId('account-id-test')),
            new StubCurrentRolesProvider([Role::Owner]),
        );

        $this->assertTrue($sut->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCannotListNorEditAnEventReportWithoutEventsFeature(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Event, accountId: new AccountId('account-id-test'));
        $sut = $this->createSUTWithFeatures('account-id-test', new AccountFeatures(events: false));

        $this->assertFalse($sut->canDoThat($subject, Action::List, $user));
        $this->assertFalse($sut->canDoThat($subject, Action::Edit, $user));
        $this->assertFalse($sut->canDoThat($subject, Action::View, $user));
    }

    public function testOwnerCanEditAnEventReportWithoutOrganizationalCarbonFootprintFeature(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Event, accountId: new AccountId('account-id-test'));
        $sut = $this->createSUTWithFeatures('account-id-test', new AccountFeatures(organizationalCarbonFootprint: false));

        $this->assertTrue($sut->canDoThat($subject, Action::List, $user));
        $this->assertTrue($sut->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCannotListNorEditOrganizationalReportsWithoutOrganizationalCarbonFootprintFeature(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $sut = $this->createSUTWithFeatures('account-id-test', new AccountFeatures(organizationalCarbonFootprint: false));

        foreach ([CarbonFootprintReportSubjectType::Property, CarbonFootprintReportSubjectType::Company] as $subjectType) {
            $subject = CarbonFootprintReportMother::registeredFor($subjectType, 'footprint-id', new AccountId('account-id-test'));

            $this->assertFalse($sut->canDoThat($subject, Action::List, $user), $subjectType->value);
            $this->assertFalse($sut->canDoThat($subject, Action::Edit, $user), $subjectType->value);
        }
    }

    public function testOwnerCanEditOrganizationalReportsWithoutEventsFeature(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Property, 'footprint-id', new AccountId('account-id-test'));
        $sut = $this->createSUTWithFeatures('account-id-test', new AccountFeatures(events: false));

        $this->assertTrue($sut->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCanViewAndRemoveReportsWithFeaturesDisabled(): void
    {
        $subject = CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Property, 'footprint-id', new AccountId('account-id-test'));
        $sut = $this->createSUTWithFeatures('account-id-test', new AccountFeatures(events: false, organizationalCarbonFootprint: false));

        $this->assertTrue($sut->canDoThat($subject, Action::View, UserMother::adminUser()));
        $this->assertTrue($sut->canDoThat($subject, Action::Remove, UserMother::adminUser()));
    }

    private function createSUTWithFeatures(string $accountId, AccountFeatures $features): CarbonFootprintReportAuthorizer
    {
        return new CarbonFootprintReportAuthorizer(
            currentAccountIdProvider: new StubCurrentAccountIdProvider(new AccountId($accountId)),
            accountFeaturesPolicy: AccountFeaturesPolicyMother::forAccount(new AccountId($accountId), $features),
        );
    }
}
