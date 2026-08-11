<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ContributionCertificate;

use App\Balance\Application\ContributionCertificate\ContributionCertificateAuthorizer;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificate;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ContributionCertificateAuthorizerTest extends TestCase
{
    public function testAdminReadsEveryCertificateButNeverWritesOne(): void
    {
        $user = UserMother::adminUser();
        $subject = $this->subject(AccountId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::View, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Register, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerReadsTheCertificatesOfOwnAccountOnly(): void
    {
        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $this->assertTrue($this->createSUT()->canDoThat($this->subject($accountId), Action::View, $user));
        $this->assertFalse($this->createSUT()->canDoThat($this->subject(AccountId::generate()), Action::View, $user));
        $this->assertFalse($this->createSUT()->canDoThat($this->subject(null), Action::View, $user));
    }

    public function testUserWithoutAccountIsDenied(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);

        $this->assertFalse($this->createSUT()->canDoThat($this->subject(AccountId::generate()), Action::List, $user));
    }

    public function testSupportsOnlyContributionCertificates(): void
    {
        $this->assertTrue($this->createSUT()->supports($this->subject(null)));
        $this->assertFalse($this->createSUT()->supports(ContributionCertificateId::generate()));
    }

    private function subject(?AccountId $accountId): ContributionCertificate
    {
        return new ContributionCertificate(id: ContributionCertificateId::generate(), accountId: $accountId);
    }

    private function createSUT(): ContributionCertificateAuthorizer
    {
        return new ContributionCertificateAuthorizer();
    }
}
