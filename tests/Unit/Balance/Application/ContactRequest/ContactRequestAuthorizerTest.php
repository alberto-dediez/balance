<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ContactRequest;

use App\Balance\Application\ContactRequest\ContactRequestAuthorizer;
use App\Balance\Domain\Model\ContactRequest\ContactRequest;
use App\Balance\Domain\Model\ContactRequest\ContactRequestId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ContactRequestAuthorizerTest extends TestCase
{
    public function testAdminCanListViewAndEdit(): void
    {
        $sut = new ContactRequestAuthorizer();
        $subject = new ContactRequest(id: ContactRequestId::generate());
        $user = UserMother::adminUser();

        $this->assertTrue($sut->canDoThat($subject, Action::List, $user));
        $this->assertTrue($sut->canDoThat($subject, Action::View, $user));
        $this->assertTrue($sut->canDoThat($subject, Action::Edit, $user));
    }

    public function testSuperAdminCanListViewAndEdit(): void
    {
        $sut = new ContactRequestAuthorizer();
        $subject = new ContactRequest(id: ContactRequestId::generate());
        $user = UserMother::fromUsernameAndRole('superadmin', Role::SuperAdmin);

        $this->assertTrue($sut->canDoThat($subject, Action::List, $user));
        $this->assertTrue($sut->canDoThat($subject, Action::View, $user));
        $this->assertTrue($sut->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCannotListViewNorEdit(): void
    {
        $sut = new ContactRequestAuthorizer();
        $subject = new ContactRequest(id: ContactRequestId::generate());
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);

        $this->assertFalse($sut->canDoThat($subject, Action::List, $user));
        $this->assertFalse($sut->canDoThat($subject, Action::View, $user));
        $this->assertFalse($sut->canDoThat($subject, Action::Edit, $user));
    }

    public function testUserCannotListViewNorEdit(): void
    {
        $sut = new ContactRequestAuthorizer();
        $subject = new ContactRequest(id: ContactRequestId::generate());
        $user = UserMother::fromUsernameAndRole('user', Role::User);

        $this->assertFalse($sut->canDoThat($subject, Action::List, $user));
        $this->assertFalse($sut->canDoThat($subject, Action::View, $user));
        $this->assertFalse($sut->canDoThat($subject, Action::Edit, $user));
    }

    public function testNobodyCanRegisterNorRemove(): void
    {
        $sut = new ContactRequestAuthorizer();
        $subject = new ContactRequest(id: ContactRequestId::generate());

        foreach ([UserMother::adminUser(), UserMother::fromUsernameAndRole('superadmin', Role::SuperAdmin), UserMother::fromUsernameAndRole('owner', Role::Owner)] as $user) {
            $this->assertFalse($sut->canDoThat($subject, Action::Register, $user));
            $this->assertFalse($sut->canDoThat($subject, Action::Remove, $user));
        }
    }
}
