<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\BaseYearDeclaration;

use App\CarbonFootprint\Application\BaseYearDeclaration\BaseYearDeclarationAuthorizer;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountFeatures;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Tests\Unit\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclarationMother;
use App\Tests\Unit\Security\Application\Account\AccountFeaturesPolicyMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Application\CurrentAccount\StubCurrentAccountIdProvider;
use PHPUnit\Framework\TestCase;

class BaseYearDeclarationAuthorizerTest extends TestCase
{
    public function testOwnerOfTheAccountCanManage(): void
    {
        $declaration = BaseYearDeclarationMother::declared(accountId: 'account-1');
        $sut = $this->createSUT(new AccountFeatures());

        foreach ([Action::List, Action::Register, Action::View, Action::Edit, Action::Remove] as $action) {
            $this->assertTrue($sut->canDoThat($declaration, $action, $this->owner()), $action->value);
        }
    }

    public function testAdminCannotEdit(): void
    {
        $declaration = BaseYearDeclarationMother::declared(accountId: 'account-1');

        $this->assertFalse((new BaseYearDeclarationAuthorizer())->canDoThat($declaration, Action::Edit, UserMother::adminUser()));
    }

    public function testOwnerCannotManageWithoutOrganizationalCarbonFootprintFeature(): void
    {
        $declaration = BaseYearDeclarationMother::declared(accountId: 'account-1');
        $sut = $this->createSUT(new AccountFeatures(organizationalCarbonFootprint: false));

        foreach ([Action::List, Action::Register, Action::View, Action::Edit, Action::Remove] as $action) {
            $this->assertFalse($sut->canDoThat($declaration, $action, $this->owner()), $action->value);
        }
    }

    private function owner(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-1'));
    }

    private function createSUT(AccountFeatures $features): BaseYearDeclarationAuthorizer
    {
        return new BaseYearDeclarationAuthorizer(
            currentAccountIdProvider: new StubCurrentAccountIdProvider(new AccountId('account-1')),
            accountFeaturesPolicy: AccountFeaturesPolicyMother::forAccount(new AccountId('account-1'), $features),
        );
    }
}
