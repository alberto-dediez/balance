<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Dashboard;

use App\FoodLoss\Application\Dashboard\AdminDashboardAuthorizer;
use App\FoodLoss\Application\Dashboard\GetAdminDashboardRequest;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class AdminDashboardAuthorizerTest extends TestCase
{
    public function testAdminCanListDashboard(): void
    {
        $user = UserMother::adminUser();
        $subject = new GetAdminDashboardRequest();

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testOwnerCannotListDashboard(): void
    {
        $user = UserMother::ownerUser();
        $subject = new GetAdminDashboardRequest();

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListDashboard(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new GetAdminDashboardRequest();

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCannotRegisterEditOrRemove(): void
    {
        $user = UserMother::adminUser();
        $subject = new GetAdminDashboardRequest();

        $sut = $this->createSUT();

        $this->assertFalse($sut->canDoThat($subject, Action::Register, $user));
        $this->assertFalse($sut->canDoThat($subject, Action::Edit, $user));
        $this->assertFalse($sut->canDoThat($subject, Action::Remove, $user));
    }

    private function createSUT(): AdminDashboardAuthorizer
    {
        return new AdminDashboardAuthorizer();
    }
}
