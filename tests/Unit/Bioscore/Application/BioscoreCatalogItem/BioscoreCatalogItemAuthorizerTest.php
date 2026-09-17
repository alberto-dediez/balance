<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Application\BioscoreCatalogItem;

use App\Bioscore\Application\BioscoreCatalogItem\BioscoreCatalogItemAuthorizer;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItem;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class BioscoreCatalogItemAuthorizerTest extends TestCase
{
    public function testAdminCanListEditAndAdmin(): void
    {
        $user = UserMother::adminUser();

        $this->assertTrue($this->createSUT()->canDoThat($this->subject(), Action::List, $user));
        $this->assertTrue($this->createSUT()->canDoThat($this->subject(), Action::Edit, $user));
        $this->assertTrue($this->createSUT()->canDoThat($this->subject(), Action::Admin, $user));
        $this->assertTrue($this->createSUT()->canDoThat($this->subject(), Action::View, $user));
        $this->assertTrue($this->createSUT()->canDoThat($this->subject(), Action::Register, $user));
    }

    public function testSuperAdminCanListEditAndAdmin(): void
    {
        $user = UserMother::superAdminUser();

        $this->assertTrue($this->createSUT()->canDoThat($this->subject(), Action::List, $user));
        $this->assertTrue($this->createSUT()->canDoThat($this->subject(), Action::Edit, $user));
        $this->assertTrue($this->createSUT()->canDoThat($this->subject(), Action::Admin, $user));
    }

    public function testOwnerUserAndProviderCannot(): void
    {
        foreach ([Role::Owner, Role::User, Role::Provider] as $role) {
            $user = UserMother::fromUsernameAndRole('someone', $role);

            $this->assertFalse($this->createSUT()->canDoThat($this->subject(), Action::List, $user));
            $this->assertFalse($this->createSUT()->canDoThat($this->subject(), Action::Edit, $user));
            $this->assertFalse($this->createSUT()->canDoThat($this->subject(), Action::Admin, $user));
        }
    }

    public function testNobodyCanRemove(): void
    {
        $this->assertFalse($this->createSUT()->canDoThat($this->subject(), Action::Remove, UserMother::superAdminUser()));
        $this->assertFalse($this->createSUT()->canDoThat($this->subject(), Action::Remove, UserMother::adminUser()));
    }

    public function testDoesNotSupportOtherSubjects(): void
    {
        $this->assertFalse($this->createSUT()->canDoThat(new \stdClass(), Action::List, UserMother::adminUser()));
    }

    private function subject(): BioscoreCatalogItem
    {
        return new BioscoreCatalogItem(id: BioscoreCatalogItemId::generate());
    }

    private function createSUT(): BioscoreCatalogItemAuthorizer
    {
        return new BioscoreCatalogItemAuthorizer();
    }
}
