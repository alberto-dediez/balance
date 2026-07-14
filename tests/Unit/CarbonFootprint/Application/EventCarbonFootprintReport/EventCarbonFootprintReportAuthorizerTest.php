<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EventCarbonFootprintReport;

use App\CarbonFootprint\Application\EventCarbonFootprintReport\EventCarbonFootprintReportAuthorizer;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class EventCarbonFootprintReportAuthorizerTest extends TestCase
{
    public function testOwnerCanViewReportFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = EventCarbonFootprintReportMother::pending(accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::View, $user));
    }

    public function testOwnerCannotViewReportFromOtherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = EventCarbonFootprintReportMother::pending(accountId: 'other-account-id');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::View, $user));
    }

    public function testOwnerCanRemoveReportFromSameAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
        $subject = EventCarbonFootprintReportMother::generated(accountId: 'account-id-test');

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testAdminCannotView(): void
    {
        $user = UserMother::adminUser();
        $subject = EventCarbonFootprintReportMother::pending(accountId: 'account-id-test');

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::View, $user));
    }

    private function createSUT(): EventCarbonFootprintReportAuthorizer
    {
        return new EventCarbonFootprintReportAuthorizer();
    }
}
