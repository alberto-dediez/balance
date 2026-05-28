<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\SignatureRequest;

use App\Security\Application\Action;
use App\Security\Application\SignatureRequest\SignatureRequestAuthorizer;
use App\Security\Domain\Model\SignatureRequest\SignatureRequest;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class SignatureRequestAuthorizerTest extends TestCase
{
    public function testAdminCanListSignatureRequests(): void
    {
        $user = UserMother::adminUser();
        $subject = new SignatureRequest(SignatureRequestId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCanRegisterSignatureRequests(): void
    {
        $user = UserMother::adminUser();
        $subject = new SignatureRequest(SignatureRequestId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testNobodyCanEditSignatureRequests(): void
    {
        $user = UserMother::adminUser();
        $subject = new SignatureRequest(SignatureRequestId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCanRemovePendingSignatureRequests(): void
    {
        $user = UserMother::adminUser();
        $subject = \App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestMother::registeredSignatureRequest();

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotRemoveSignatureRequests(): void
    {
        $user = UserMother::ownerUser();
        $subject = \App\Tests\Unit\Security\Domain\Model\SignatureRequest\SignatureRequestMother::registeredSignatureRequest();

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotListSignatureRequests(): void
    {
        $user = UserMother::ownerUser();
        $subject = new SignatureRequest(SignatureRequestId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testNormalUserCannotListSignatureRequests(): void
    {
        $user = UserMother::fromUsernameAndRole('user', \App\Security\Domain\Model\User\Role::User);
        $subject = new SignatureRequest(SignatureRequestId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): SignatureRequestAuthorizer
    {
        return new SignatureRequestAuthorizer();
    }
}
