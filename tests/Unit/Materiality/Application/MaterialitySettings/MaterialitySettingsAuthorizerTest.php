<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialitySettings;

use App\Materiality\Application\MaterialitySettings\MaterialitySettingsAuthorizer;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettings;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialitySettingsId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class MaterialitySettingsAuthorizerTest extends TestCase
{
    public function testAdminAndSuperAdminCanViewAndEdit(): void
    {
        $subject = new MaterialitySettings(MaterialitySettingsId::generate());

        foreach ([UserMother::adminUser(), UserMother::fromUsernameAndRole('superadmin', Role::SuperAdmin)] as $user) {
            foreach ([Action::View, Action::Edit] as $action) {
                $this->assertTrue($this->createSUT()->canDoThat($subject, $action, $user), $action->value);
            }
        }
    }

    public function testOwnerAndUserCannotViewNorEdit(): void
    {
        $subject = new MaterialitySettings(MaterialitySettingsId::generate());

        foreach ([UserMother::fromUsernameAndRole('owner', Role::Owner), UserMother::fromUsernameAndRole('user', Role::User)] as $user) {
            foreach ([Action::View, Action::Edit] as $action) {
                $this->assertFalse($this->createSUT()->canDoThat($subject, $action, $user), $action->value);
            }
        }
    }

    public function testNobodyCanRemove(): void
    {
        $this->assertFalse($this->createSUT()->canDoThat(new MaterialitySettings(MaterialitySettingsId::generate()), Action::Remove, UserMother::fromUsernameAndRole('superadmin', Role::SuperAdmin)));
    }

    private function createSUT(): MaterialitySettingsAuthorizer
    {
        return new MaterialitySettingsAuthorizer();
    }
}
