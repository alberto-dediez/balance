<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Application\MaterialitySector;

use App\Materiality\Application\MaterialitySector\MaterialitySectorAuthorizer;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySector;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class MaterialitySectorAuthorizerTest extends TestCase
{
    public function testAdminAndSuperAdminCanManage(): void
    {
        $subject = new MaterialitySector(MaterialitySectorId::generate());

        foreach ([UserMother::adminUser(), UserMother::fromUsernameAndRole('superadmin', Role::SuperAdmin)] as $user) {
            foreach ([Action::List, Action::Register, Action::Edit, Action::View, Action::Remove] as $action) {
                $this->assertTrue($this->createSUT()->canDoThat($subject, $action, $user), $action->value);
            }
        }
    }

    public function testOwnerAndUserCannotManage(): void
    {
        $subject = new MaterialitySector(MaterialitySectorId::generate());

        foreach ([UserMother::fromUsernameAndRole('owner', Role::Owner), UserMother::fromUsernameAndRole('user', Role::User)] as $user) {
            foreach ([Action::List, Action::Register, Action::Edit, Action::View, Action::Remove] as $action) {
                $this->assertFalse($this->createSUT()->canDoThat($subject, $action, $user), $action->value);
            }
        }
    }

    public function testNobodyCanImportExportOrAutocomplete(): void
    {
        $subject = new MaterialitySector(MaterialitySectorId::generate());

        foreach ([Action::Import, Action::Export, Action::Autocomplete] as $action) {
            $this->assertFalse($this->createSUT()->canDoThat($subject, $action, UserMother::fromUsernameAndRole('superadmin', Role::SuperAdmin)), $action->value);
        }
    }

    private function createSUT(): MaterialitySectorAuthorizer
    {
        return new MaterialitySectorAuthorizer();
    }
}
