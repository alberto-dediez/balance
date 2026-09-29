<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MitigationMeasureCategory;

use App\CarbonFootprint\Application\MitigationMeasureCategory\MitigationMeasureCategoryAuthorizer;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategory;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class MitigationMeasureCategoryAuthorizerTest extends TestCase
{
    public function testAdminAndSuperAdminCanManage(): void
    {
        $subject = new MitigationMeasureCategory(MitigationMeasureCategoryId::generate());

        foreach ([UserMother::adminUser(), UserMother::fromUsernameAndRole('superadmin', Role::SuperAdmin)] as $user) {
            foreach ([Action::List, Action::Register, Action::Edit, Action::View, Action::Remove] as $action) {
                $this->assertTrue((new MitigationMeasureCategoryAuthorizer())->canDoThat($subject, $action, $user), $action->value);
            }
        }
    }

    public function testOwnerAndUserCannotManage(): void
    {
        $subject = new MitigationMeasureCategory(MitigationMeasureCategoryId::generate());

        foreach ([UserMother::fromUsernameAndRole('owner', Role::Owner), UserMother::fromUsernameAndRole('user', Role::User)] as $user) {
            foreach ([Action::List, Action::Register, Action::Edit, Action::View, Action::Remove] as $action) {
                $this->assertFalse((new MitigationMeasureCategoryAuthorizer())->canDoThat($subject, $action, $user), $action->value);
            }
        }
    }

    public function testNobodyCanImportExportOrAutocomplete(): void
    {
        $subject = new MitigationMeasureCategory(MitigationMeasureCategoryId::generate());

        foreach ([Action::Import, Action::Export, Action::Autocomplete] as $action) {
            $this->assertFalse((new MitigationMeasureCategoryAuthorizer())->canDoThat($subject, $action, UserMother::adminUser()), $action->value);
        }
    }
}
