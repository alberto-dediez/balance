<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Application\Notification;

use App\Notifications\Application\Notification\NotificationAuthorizer;
use App\Notifications\Domain\Model\Notification\Notification;
use App\Notifications\Domain\Model\Notification\NotificationId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Notifications\Domain\Model\Notification\NotificationMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class NotificationAuthorizerTest extends TestCase
{
    public function testAdminCanRegisterAndList(): void
    {
        $user = UserMother::adminUser();
        $subject = new Notification(NotificationId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testNormalUserCannotRegisterNorList(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new Notification(NotificationId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Register, $user));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testRecipientCanViewOwnNotification(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = NotificationMother::internalFor($user->id());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::View, $user));
    }

    public function testNonRecipientUserCannotViewNotification(): void
    {
        $owner = UserMother::fromUsernameAndRole('owner', Role::User);
        $subject = NotificationMother::randomInternal();

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::View, $owner));
    }

    private function createSUT(): NotificationAuthorizer
    {
        return new NotificationAuthorizer();
    }
}
