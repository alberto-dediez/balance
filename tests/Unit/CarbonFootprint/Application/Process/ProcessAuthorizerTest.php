<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Process;

use App\CarbonFootprint\Application\Process\ProcessAuthorizer;
use App\CarbonFootprint\Domain\Model\Process\Process;
use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ProcessAuthorizerTest extends TestCase
{
    /** @return array<string, array{Role, Action, bool}> */
    public static function permissions(): array
    {
        $permissions = [];

        foreach ([Action::List, Action::Register, Action::Edit, Action::Remove, Action::Import, Action::Export] as $action) {
            $permissions['super admin ' . $action->value] = [Role::SuperAdmin, $action, true];
            $permissions['admin ' . $action->value] = [Role::Admin, $action, true];
            $permissions['owner ' . $action->value] = [Role::Owner, $action, false];
            $permissions['user ' . $action->value] = [Role::User, $action, false];
        }

        return $permissions;
    }

    /** @dataProvider permissions */
    public function testPermissions(Role $role, Action $action, bool $expected): void
    {
        $user = UserMother::fromUsernameAndRole('username', $role);
        $subject = new Process(ProcessId::generate());

        $this->assertSame($expected, $this->createSUT()->canDoThat($subject, $action, $user));
    }

    private function createSUT(): ProcessAuthorizer
    {
        return new ProcessAuthorizer();
    }
}
