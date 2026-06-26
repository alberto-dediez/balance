<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CateringItem;

use App\CarbonFootprint\Application\CateringItem\CateringItemAuthorizer;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItem;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CateringItemAuthorizerTest extends TestCase
{
    public function testAdminCanListCateringItems(): void
    {
        $user = UserMother::adminUser();
        $subject = new CateringItem(CateringItemId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCanRegisterCateringItems(): void
    {
        $user = UserMother::adminUser();
        $subject = new CateringItem(CateringItemId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testAdminCanEditCateringItem(): void
    {
        $user = UserMother::adminUser();
        $subject = new CateringItem(CateringItemId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCanRemoveCateringItem(): void
    {
        $user = UserMother::adminUser();
        $subject = new CateringItem(CateringItemId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotListCateringItems(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new CateringItem(CateringItemId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListCateringItems(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new CateringItem(CateringItemId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): CateringItemAuthorizer
    {
        return new CateringItemAuthorizer();
    }
}
