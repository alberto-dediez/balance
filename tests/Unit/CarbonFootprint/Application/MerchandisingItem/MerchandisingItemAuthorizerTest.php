<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MerchandisingItem;

use App\CarbonFootprint\Application\MerchandisingItem\MerchandisingItemAuthorizer;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItem;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class MerchandisingItemAuthorizerTest extends TestCase
{
    public function testAdminCanListMerchandisingItems(): void
    {
        $user = UserMother::adminUser();
        $subject = new MerchandisingItem(MerchandisingItemId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testAdminCanRegisterMerchandisingItems(): void
    {
        $user = UserMother::adminUser();
        $subject = new MerchandisingItem(MerchandisingItemId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Register, $user));
    }

    public function testAdminCanEditMerchandisingItem(): void
    {
        $user = UserMother::adminUser();
        $subject = new MerchandisingItem(MerchandisingItemId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Edit, $user));
    }

    public function testAdminCanRemoveMerchandisingItem(): void
    {
        $user = UserMother::adminUser();
        $subject = new MerchandisingItem(MerchandisingItemId::generate());

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, $user));
    }

    public function testOwnerCannotListMerchandisingItems(): void
    {
        $user = UserMother::fromUsernameAndRole('owner', Role::Owner);
        $subject = new MerchandisingItem(MerchandisingItemId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    public function testUserCannotListMerchandisingItems(): void
    {
        $user = UserMother::fromUsernameAndRole('user', Role::User);
        $subject = new MerchandisingItem(MerchandisingItemId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::List, $user));
    }

    private function createSUT(): MerchandisingItemAuthorizer
    {
        return new MerchandisingItemAuthorizer();
    }
}
