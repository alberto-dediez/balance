<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\EmailLog;

use App\Security\Application\Action;
use App\Security\Application\EmailLog\EmailLogAuthorizer;
use App\Security\Domain\Model\User\User;
use App\Tests\Unit\Security\Domain\Model\EmailLog\EmailLogMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class EmailLogAuthorizerTest extends TestCase
{
    public function testSuperAdminCanListAndView(): void
    {
        $sut = new EmailLogAuthorizer();
        $emailLog = EmailLogMother::sent();

        $this->assertTrue($sut->canDoThat($emailLog, Action::List, UserMother::superAdminUser()));
        $this->assertTrue($sut->canDoThat($emailLog, Action::View, UserMother::superAdminUser()));
    }

    public function testSuperAdminCanSeeHistory(): void
    {
        $sut = new EmailLogAuthorizer();
        $emailLog = EmailLogMother::sent();

        $this->assertTrue($sut->canDoThat($emailLog, Action::History, UserMother::superAdminUser()));
    }

    /**
     * @dataProvider nonSuperAdminUsers
     */
    public function testNonSuperAdminCannotSeeHistory(User $user): void
    {
        $sut = new EmailLogAuthorizer();
        $emailLog = EmailLogMother::sent();

        $this->assertFalse($sut->canDoThat($emailLog, Action::History, $user));
    }

    /**
     * @return array<string, array{0: User}>
     */
    public static function nonSuperAdminUsers(): array
    {
        return [
            'admin' => [UserMother::adminUser()],
            'owner' => [UserMother::ownerUser()],
            'user' => [UserMother::registeredUser()],
        ];
    }

    /**
     * @dataProvider nonSuperAdminUsers
     */
    public function testNonSuperAdminCannotListNorView(User $user): void
    {
        $sut = new EmailLogAuthorizer();
        $emailLog = EmailLogMother::sent();

        $this->assertFalse($sut->canDoThat($emailLog, Action::List, $user));
        $this->assertFalse($sut->canDoThat($emailLog, Action::View, $user));
    }

    /**
     * @return array<string, array{0: User}>
     */
    public static function allUsers(): array
    {
        return [
            'super admin' => [UserMother::superAdminUser()],
            'admin' => [UserMother::adminUser()],
            'owner' => [UserMother::ownerUser()],
            'user' => [UserMother::registeredUser()],
        ];
    }

    /**
     * @dataProvider allUsers
     */
    public function testNobodyCanRegisterEditNorRemove(User $user): void
    {
        $sut = new EmailLogAuthorizer();
        $emailLog = EmailLogMother::sent();

        $this->assertFalse($sut->canDoThat($emailLog, Action::Register, $user));
        $this->assertFalse($sut->canDoThat($emailLog, Action::Edit, $user));
        $this->assertFalse($sut->canDoThat($emailLog, Action::Remove, $user));
    }
}
