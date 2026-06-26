<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ElectricityMarketer;

use App\CarbonFootprint\Application\ElectricityMarketer\ElectricityMarketerAuthorizer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketer;
use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ElectricityMarketerAuthorizerTest extends TestCase
{
    public function testAdminCanListElectricityMarketers(): void
    {
        $user = UserMother::adminUser();
        $subject = new ElectricityMarketer(ElectricityMarketerId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCanRegisterElectricityMarketers(): void
    {
        $user = UserMother::adminUser();
        $subject = new ElectricityMarketer(ElectricityMarketerId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testAdminCanEditElectricityMarketer(): void
    {
        $user = UserMother::adminUser();
        $subject = new ElectricityMarketer(ElectricityMarketerId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCanRemoveElectricityMarketer(): void
    {
        $user = UserMother::adminUser();
        $subject = new ElectricityMarketer(ElectricityMarketerId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotListElectricityMarketers(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new ElectricityMarketer(ElectricityMarketerId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListElectricityMarketers(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new ElectricityMarketer(ElectricityMarketerId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): ElectricityMarketerAuthorizer
    {
        return new ElectricityMarketerAuthorizer();
    }
}
