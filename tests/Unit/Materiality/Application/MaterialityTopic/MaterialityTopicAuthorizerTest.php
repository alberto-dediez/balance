<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityTopic;

use App\Materiality\Application\MaterialityTopic\MaterialityTopicAuthorizer;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopic;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use App\Materiality\Domain\Model\MaterialityTopic\StandardScope3Category;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class MaterialityTopicAuthorizerTest extends TestCase
{
    public function testAdminAndSuperAdminCanManage(): void
    {
        $subject = new MaterialityTopic(MaterialityTopicId::generate());

        foreach ([UserMother::adminUser(), UserMother::fromUsernameAndRole('superadmin', Role::SuperAdmin)] as $user) {
            foreach ([Action::List, Action::Register, Action::Edit, Action::View, Action::Admin] as $action) {
                $this->assertTrue($this->createSUT()->canDoThat($subject, $action, $user), $action->value);
            }
        }
    }

    public function testOwnerAndUserCannotManage(): void
    {
        $subject = new MaterialityTopic(MaterialityTopicId::generate());

        foreach ([UserMother::fromUsernameAndRole('owner', Role::Owner), UserMother::fromUsernameAndRole('user', Role::User)] as $user) {
            foreach ([Action::List, Action::Register, Action::Edit, Action::View, Action::Remove, Action::Admin] as $action) {
                $this->assertFalse($this->createSUT()->canDoThat($subject, $action, $user), $action->value);
            }
        }
    }

    public function testAdminCanRemoveOwnTopic(): void
    {
        $subject = MaterialityTopicMother::registeredMaterialityTopic();

        $this->assertTrue($this->createSUT()->canDoThat($subject, Action::Remove, UserMother::adminUser()));
    }

    public function testNobodyCanRemoveStandardTopic(): void
    {
        $subject = MaterialityTopicMother::standard(StandardScope3Category::Franchises);

        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Remove, UserMother::adminUser()));
        $this->assertFalse($this->createSUT()->canDoThat($subject, Action::Remove, UserMother::fromUsernameAndRole('superadmin', Role::SuperAdmin)));
    }

    private function createSUT(): MaterialityTopicAuthorizer
    {
        return new MaterialityTopicAuthorizer();
    }
}
