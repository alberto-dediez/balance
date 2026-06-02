<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\AlertConfiguration;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Waste\Domain\Model\AlertConfiguration\AlertConfigurationMother;
use App\Waste\Application\AlertConfiguration\AlertConfigurationAuthorizer;
use PHPUnit\Framework\TestCase;

class AlertConfigurationAuthorizerTest extends TestCase
{
    public function testAdminCanListAlertConfigurations(): void
    {
        $user = UserMother::adminUser();
        $subject = AlertConfigurationMother::registeredAlertConfiguration();

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCanRegisterAlertConfigurations(): void
    {
        $user = UserMother::adminUser();
        $subject = AlertConfigurationMother::registeredAlertConfiguration();

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testAdminCanEditAlertConfigurations(): void
    {
        $user = UserMother::adminUser();
        $subject = AlertConfigurationMother::registeredAlertConfiguration();

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testOwnerCannotListAlertConfigurations(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = AlertConfigurationMother::registeredAlertConfiguration();

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListAlertConfigurations(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = AlertConfigurationMother::registeredAlertConfiguration();

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): AlertConfigurationAuthorizer
    {
        return new AlertConfigurationAuthorizer();
    }
}
