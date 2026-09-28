<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialityQuestion;

use App\Materiality\Application\MaterialityQuestion\MaterialityQuestionAuthorizer;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestion;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityQuestionId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class MaterialityQuestionAuthorizerTest extends TestCase
{
    public function testAdminAndSuperAdminCanManage(): void
    {
        $subject = new MaterialityQuestion(MaterialityQuestionId::generate());

        foreach ([UserMother::adminUser(), UserMother::fromUsernameAndRole('superadmin', Role::SuperAdmin)] as $user) {
            foreach ([Action::List, Action::Register, Action::Edit, Action::View, Action::Remove] as $action) {
                $this->assertTrue($this->createSUT()->canDoThat($subject, $action, $user), $action->value);
            }
        }
    }

    public function testOwnerAndUserCannotManage(): void
    {
        $subject = new MaterialityQuestion(MaterialityQuestionId::generate());

        foreach ([UserMother::fromUsernameAndRole('owner', Role::Owner), UserMother::fromUsernameAndRole('user', Role::User)] as $user) {
            foreach ([Action::List, Action::Register, Action::Edit, Action::View, Action::Remove] as $action) {
                $this->assertFalse($this->createSUT()->canDoThat($subject, $action, $user), $action->value);
            }
        }
    }

    private function createSUT(): MaterialityQuestionAuthorizer
    {
        return new MaterialityQuestionAuthorizer();
    }
}
