<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintKpi;

use App\CarbonFootprint\Application\CarbonFootprintKpi\CarbonFootprintKpiAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintKpi\KpiSubject;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

final class CarbonFootprintKpiAuthorizerTest extends TestCase
{
    public function testOwnerOfTheSameAccountCanViewAndEdit(): void
    {
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-1'));

        $this->assertTrue((new CarbonFootprintKpiAuthorizer())->canDoThat($this->subject('account-1'), Action::View, $owner));
        $this->assertTrue((new CarbonFootprintKpiAuthorizer())->canDoThat($this->subject('account-1'), Action::Edit, $owner));
        $this->assertTrue((new CarbonFootprintKpiAuthorizer())->canDoThat($this->subject('account-1'), Action::List, $owner));
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-2'));

        $this->assertFalse((new CarbonFootprintKpiAuthorizer())->canDoThat($this->subject('account-1'), Action::View, $owner));
        $this->assertFalse((new CarbonFootprintKpiAuthorizer())->canDoThat($this->subject('account-1'), Action::Edit, $owner));
    }

    public function testAdminIsDenied(): void
    {
        $this->assertFalse((new CarbonFootprintKpiAuthorizer())->canDoThat($this->subject('account-1'), Action::View, UserMother::adminUser()));
        $this->assertFalse((new CarbonFootprintKpiAuthorizer())->canDoThat($this->subject('account-1'), Action::List, UserMother::adminUser()));
    }

    public function testImpersonatingTheAccountOfTheSubjectCanView(): void
    {
        $owner = UserMother::fromUsernameAndRoleAndAccount('consultant', Role::Owner, new AccountId('consultancy'));
        $authorizer = new CarbonFootprintKpiAuthorizer(new StubCurrentAccountIdProvider(new AccountId('account-1')));

        $this->assertTrue($authorizer->canDoThat($this->subject('account-1'), Action::View, $owner));
        $this->assertFalse($authorizer->canDoThat($this->subject('consultancy'), Action::View, $owner));
    }

    public function testOwnerCannotViewNorListWithoutOrganizationalCarbonFootprintFeature(): void
    {
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-1'));
        $sut = $this->createSUTWithFeatures('account-1', new AccountFeatures(organizationalCarbonFootprint: false));

        $this->assertFalse($sut->canDoThat($this->subject('account-1'), Action::View, $owner));
        $this->assertFalse($sut->canDoThat($this->subject('account-1'), Action::Edit, $owner));
        $this->assertFalse($sut->canDoThat($this->subject('account-1'), Action::List, $owner));
    }

    public function testOwnerCanViewWithOrganizationalCarbonFootprintFeature(): void
    {
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-1'));
        $sut = $this->createSUTWithFeatures('account-1', new AccountFeatures(events: false));

        $this->assertTrue($sut->canDoThat($this->subject('account-1'), Action::View, $owner));
        $this->assertTrue($sut->canDoThat($this->subject('account-1'), Action::List, $owner));
    }

    private function subject(string $accountId): KpiSubject
    {
        return new KpiSubject(CarbonFootprintReportSubjectType::Property, 'property-1', new AccountId($accountId), 'Hotel', 'Hotel');
    }

    private function createSUTWithFeatures(string $accountId, AccountFeatures $features): CarbonFootprintKpiAuthorizer
    {
        return new CarbonFootprintKpiAuthorizer(
            currentAccountIdProvider: new StubCurrentAccountIdProvider(new AccountId($accountId)),
            accountFeaturesPolicy: AccountFeaturesPolicyMother::forAccount(new AccountId($accountId), $features),
        );
    }
}
