<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprintCertificate;

use App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateAuthorizer;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificate;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateId;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class PropertyCarbonFootprintCertificateAuthorizerTest extends TestCase
{
    public function testOwnerViewsTheCertificatesOfOwnAccount(): void
    {
        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $this->assertTrue($this->createSUT()->canDoThat($this->subject($accountId), Action::View, $user));
        $this->assertTrue($this->createSUT()->canDoThat($this->subject($accountId), Action::List, $user));
    }

    public function testOwnerOfAnotherAccountCannotView(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $this->assertFalse($this->createSUT()->canDoThat($this->subject(AccountId::generate()), Action::View, $user));
        $this->assertFalse($this->createSUT()->canDoThat($this->subject(null), Action::View, $user));
    }

    public function testAdminViewsEveryCertificate(): void
    {
        $this->assertTrue($this->createSUT()->canDoThat($this->subject(AccountId::generate()), Action::View, UserMother::adminUser()));
    }

    public function testNobodyRegistersEditsOrRemoves(): void
    {
        $accountId = AccountId::generate();

        foreach ([UserMother::adminUser(), UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId)] as $user) {
            $this->assertFalse($this->createSUT()->canDoThat($this->subject($accountId), Action::Register, $user));
            $this->assertFalse($this->createSUT()->canDoThat($this->subject($accountId), Action::Edit, $user));
            $this->assertFalse($this->createSUT()->canDoThat($this->subject($accountId), Action::Remove, $user));
        }
    }

    public function testSupportsOnlyPropertyCarbonFootprintCertificates(): void
    {
        $this->assertTrue($this->createSUT()->supports($this->subject(null)));
        $this->assertFalse($this->createSUT()->supports(PropertyCarbonFootprintCertificateId::generate()));
    }

    private function subject(?AccountId $accountId): PropertyCarbonFootprintCertificate
    {
        return new PropertyCarbonFootprintCertificate(id: PropertyCarbonFootprintCertificateId::generate(), accountId: $accountId);
    }

    private function createSUT(): PropertyCarbonFootprintCertificateAuthorizer
    {
        return new PropertyCarbonFootprintCertificateAuthorizer();
    }
}
