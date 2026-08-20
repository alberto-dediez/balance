<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Branding;

use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class BrandingAuthorizerTest extends TestCase
{
    public function testSuperAdminAndAdminCanEditAnyBranding(): void
    {
        $sut = BrandingAuthorizerMother::create();

        $branding = BrandingMother::random();

        $this->assertTrue($sut->canDoThat($branding, Action::Edit, UserMother::fromUsernameAndRole('superadmin', Role::SuperAdmin)));
        $this->assertTrue($sut->canDoThat($branding, Action::Edit, UserMother::adminUser()));
    }

    public function testOwnerCanEditOnlyItsOwnAccountBranding(): void
    {
        $sut = BrandingAuthorizerMother::create();

        $account = AccountMother::fromName('Owned');
        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $account->id());

        $this->assertTrue($sut->canDoThat(BrandingMother::forAccount($account->id()), Action::Edit, $owner));
        $this->assertFalse($sut->canDoThat(BrandingMother::forAccount(AccountId::generate()), Action::Edit, $owner));
    }

    public function testPlainUserCannotEdit(): void
    {
        $sut = BrandingAuthorizerMother::create();

        $this->assertFalse($sut->canDoThat(BrandingMother::random(), Action::Edit, UserMother::fromUsernameAndRole('plain', Role::User)));
    }

    public function testOnlyAdminsCanEditTheme(): void
    {
        $sut = BrandingAuthorizerMother::create();

        $account = AccountMother::fromName('Owned');
        $branding = BrandingMother::forAccount($account->id());

        $this->assertTrue($sut->canEditTheme($branding, UserMother::fromUsernameAndRole('superadmin', Role::SuperAdmin)));
        $this->assertTrue($sut->canEditTheme($branding, UserMother::adminUser()));
        $this->assertFalse($sut->canEditTheme($branding, UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $account->id())));
    }

    public function testDenyUnlessCanEditThemeThrowsForNonAdmins(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        BrandingAuthorizerMother::create()->denyUnlessCanEditTheme(BrandingMother::random(), UserMother::fromUsernameAndRole('plain', Role::User));
    }

    public function testSupportsOnlyBranding(): void
    {
        $sut = BrandingAuthorizerMother::create();

        $this->assertTrue($sut->supports(BrandingMother::random()));
        $this->assertFalse($sut->supports(AccountMother::registeredAccount()));
    }
}
