<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityAssessment;

use App\Materiality\Application\MaterialityAssessment\MaterialityAssessmentAuthorizer;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentMother;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

final class MaterialityAssessmentAuthorizerTest extends TestCase
{
    public function testOwnerOfTheAccountCanManage(): void
    {
        $assessment = MaterialityAssessmentMother::draft(accountId: 'account-id');
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
        $sut = new MaterialityAssessmentAuthorizer();

        foreach ([SecurityAction::Register, SecurityAction::View, SecurityAction::List, SecurityAction::Edit, SecurityAction::Remove] as $action) {
            $this->assertTrue($sut->canDoThat($assessment, $action, $owner), $action->name);
        }

        foreach ([SecurityAction::Import, SecurityAction::Export, SecurityAction::Autocomplete] as $action) {
            $this->assertFalse($sut->canDoThat($assessment, $action, $owner), $action->name);
        }
    }

    public function testOwnerOfAnotherAccountCannotManage(): void
    {
        $assessment = MaterialityAssessmentMother::draft(accountId: 'account-id');
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('other-account'));
        $sut = new MaterialityAssessmentAuthorizer();

        $this->assertFalse($sut->canDoThat($assessment, SecurityAction::View, $owner));
        $this->assertFalse($sut->canDoThat($assessment, SecurityAction::Edit, $owner));
        $this->assertFalse($sut->canDoThat($assessment, SecurityAction::Remove, $owner));
    }

    public function testOwnerImpersonatingTheAccountCanManage(): void
    {
        $assessment = MaterialityAssessmentMother::draft(accountId: 'account-id');
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('other-account'));
        $sut = new MaterialityAssessmentAuthorizer(new StubCurrentAccountIdProvider(new AccountId('account-id')));

        $this->assertTrue($sut->canDoThat($assessment, SecurityAction::Edit, $owner));
    }

    public function testUserRoleCannotManage(): void
    {
        $assessment = MaterialityAssessmentMother::draft(accountId: 'account-id');
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id'));
        $sut = new MaterialityAssessmentAuthorizer();

        $this->assertFalse($sut->canDoThat($assessment, SecurityAction::View, $user));
        $this->assertFalse($sut->canDoThat($assessment, SecurityAction::Edit, $user));
    }

    public function testAdminCanOnlyRemove(): void
    {
        $assessment = MaterialityAssessmentMother::draft(accountId: 'account-id');
        $admin = UserMother::adminUser();
        $sut = new MaterialityAssessmentAuthorizer();

        $this->assertFalse($sut->canDoThat($assessment, SecurityAction::View, $admin));
        $this->assertFalse($sut->canDoThat($assessment, SecurityAction::Edit, $admin));
        $this->assertTrue($sut->canDoThat($assessment, SecurityAction::Remove, $admin));
    }

    public function testOwnerCannotManageWithoutOrganizationalCarbonFootprintFeature(): void
    {
        $assessment = MaterialityAssessmentMother::draft(accountId: 'account-id');
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
        $sut = $this->createSUTWithFeatures('account-id', new AccountFeatures(organizationalCarbonFootprint: false));

        foreach ([SecurityAction::Register, SecurityAction::View, SecurityAction::List, SecurityAction::Edit, SecurityAction::Remove] as $action) {
            $this->assertFalse($sut->canDoThat($assessment, $action, $owner), $action->name);
        }
    }

    public function testAdminCanRemoveWithOrganizationalCarbonFootprintFeatureDisabled(): void
    {
        $assessment = MaterialityAssessmentMother::draft(accountId: 'account-id');
        $sut = $this->createSUTWithFeatures('account-id', new AccountFeatures(organizationalCarbonFootprint: false));

        $this->assertTrue($sut->canDoThat($assessment, SecurityAction::Remove, UserMother::adminUser()));
    }

    private function createSUTWithFeatures(string $accountId, AccountFeatures $features): MaterialityAssessmentAuthorizer
    {
        return new MaterialityAssessmentAuthorizer(
            currentAccountIdProvider: new StubCurrentAccountIdProvider(new AccountId($accountId)),
            accountFeaturesPolicy: AccountFeaturesPolicyMother::forAccount(new AccountId($accountId), $features),
        );
    }
}
