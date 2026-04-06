<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\DonationAgreement;

use App\FoodLoss\Application\DonationAgreement\DonationAgreementAuthorizer;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreement;
use App\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\FoodLoss\Domain\Model\DonationAgreement\DonationAgreementMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class DonationAgreementAuthorizerTest extends TestCase
{
    public function testOwnerCanListDonationAgreements(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = new DonationAgreement(id: DonationAgreementId::generate(), accountId: new AccountId('account-id-test'));

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testNormalUserCannotListDonationAgreements(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id-test'));
        $subject = new DonationAgreement(id: DonationAgreementId::generate(), accountId: new AccountId('account-id-test'));

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCanEditDonationAgreementFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = DonationAgreementMother::forAccount('account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCannotEditDonationAgreementFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = DonationAgreementMother::forAccount('other-account-id');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCannotDoAnything(): void
    {
        $user = UserMother::adminUser();
        $subject = DonationAgreementMother::random();

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    private function createSUT(): DonationAgreementAuthorizer
    {
        return new DonationAgreementAuthorizer();
    }
}
