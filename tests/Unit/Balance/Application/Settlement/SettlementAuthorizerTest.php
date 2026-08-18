<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\Settlement;

use App\Balance\Application\Settlement\SettlementAuthorizer;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntry;
use App\Balance\Domain\Model\LedgerEntry\LedgerEntryId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class SettlementAuthorizerTest extends TestCase
{
    public function testSuperAdminCanDoEverything(): void
    {
        $user = UserMother::fromUsernameAndRole('superadmin', Role::SuperAdmin);

        $this->assertAllowedForEveryAction($user);
    }

    public function testAdminCanDoEverything(): void
    {
        $user = UserMother::adminUser();

        $this->assertAllowedForEveryAction($user);
    }

    public function testOwnerCannotDoAnything(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);

        $this->assertDeniedForEveryAction($user);
    }

    public function testPlainUserCannotDoAnything(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);

        $this->assertDeniedForEveryAction($user);
    }

    public function testItOnlySupportsLedgerEntries(): void
    {
        $this->assertTrue($this->createSUT()->supports(new LedgerEntry(id: LedgerEntryId::generate())));
        $this->assertFalse($this->createSUT()->supports(new \stdClass()));
    }

    private function assertAllowedForEveryAction(User $user): void
    {
        $subject = new LedgerEntry(id: LedgerEntryId::generate());
        $sut = $this->createSUT();

        $this->assertTrue($sut->canDoThat($subject, Action::List, $user));
        $this->assertTrue($sut->canDoThat($subject, Action::View, $user));
        $this->assertTrue($sut->canDoThat($subject, Action::Register, $user));
        $this->assertTrue($sut->canDoThat($subject, Action::Edit, $user));
        $this->assertTrue($sut->canDoThat($subject, Action::Remove, $user));
    }

    private function assertDeniedForEveryAction(User $user): void
    {
        $subject = new LedgerEntry(id: LedgerEntryId::generate());
        $sut = $this->createSUT();

        $this->assertFalse($sut->canDoThat($subject, Action::List, $user));
        $this->assertFalse($sut->canDoThat($subject, Action::View, $user));
        $this->assertFalse($sut->canDoThat($subject, Action::Register, $user));
        $this->assertFalse($sut->canDoThat($subject, Action::Edit, $user));
        $this->assertFalse($sut->canDoThat($subject, Action::Remove, $user));
    }

    private function createSUT(): SettlementAuthorizer
    {
        return new SettlementAuthorizer();
    }
}
