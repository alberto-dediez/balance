<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MitigationMeasure;

use App\CarbonFootprint\Application\MitigationMeasure\MitigationMeasureAuthorizer;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasure;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class MitigationMeasureAuthorizerTest extends TestCase
{
    public function testAdminAndSuperAdminCanManage(): void
    {
        $subject = new MitigationMeasure(MitigationMeasureId::generate());

        foreach ([UserMother::adminUser(), UserMother::fromUsernameAndRole('superadmin', Role::SuperAdmin)] as $user) {
            foreach ([Action::List, Action::Register, Action::Edit, Action::View, Action::Remove] as $action) {
                $this->assertTrue((new MitigationMeasureAuthorizer())->canDoThat($subject, $action, $user), $action->value);
            }
        }
    }

    public function testOwnerAndUserCannotManage(): void
    {
        $subject = new MitigationMeasure(MitigationMeasureId::generate());

        foreach ([UserMother::fromUsernameAndRole('owner', Role::Owner), UserMother::fromUsernameAndRole('user', Role::User)] as $user) {
            foreach ([Action::List, Action::Register, Action::Edit, Action::View, Action::Remove] as $action) {
                $this->assertFalse((new MitigationMeasureAuthorizer())->canDoThat($subject, $action, $user), $action->value);
            }
        }
    }

    public function testNobodyCanImportExportOrAutocomplete(): void
    {
        $subject = new MitigationMeasure(MitigationMeasureId::generate());

        foreach ([Action::Import, Action::Export, Action::Autocomplete] as $action) {
            $this->assertFalse((new MitigationMeasureAuthorizer())->canDoThat($subject, $action, UserMother::adminUser()), $action->value);
        }
    }
}
