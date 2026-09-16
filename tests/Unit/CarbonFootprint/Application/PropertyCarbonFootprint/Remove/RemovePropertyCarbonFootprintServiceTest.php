<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprint\Remove;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Remove\RemovePropertyCarbonFootprintRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Remove\RemovePropertyCarbonFootprintService;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RemovePropertyCarbonFootprintServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testRemovesTheFootprint(): void
    {
        $repository = PropertyCarbonFootprintRepositoryMother::inMemory();
        $footprint = PropertyCarbonFootprintMother::fromPropertyIdAndAccount((string) PropertyId::generate(), 'account-id-test');
        $repository->add($footprint);

        $sut = new RemovePropertyCarbonFootprintService($repository, new PropertyCarbonFootprintAuthorizer());

        $sut(new RemovePropertyCarbonFootprintRequest($footprint->id()), $this->ownerUser());

        $this->assertNull($repository->find($footprint->id()));
        $this->assertTrue($footprint->isRemoved());
    }

    public function testUnknownFootprintThrows(): void
    {
        $sut = new RemovePropertyCarbonFootprintService(PropertyCarbonFootprintRepositoryMother::inMemory(), new PropertyCarbonFootprintAuthorizer());

        $this->expectException(\InvalidArgumentException::class);

        $sut(new RemovePropertyCarbonFootprintRequest(\App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId::generate()), $this->ownerUser());
    }

    public function testOtherAccountIsDenied(): void
    {
        $repository = PropertyCarbonFootprintRepositoryMother::inMemory();
        $footprint = PropertyCarbonFootprintMother::fromPropertyIdAndAccount((string) PropertyId::generate(), 'other-account-id');
        $repository->add($footprint);

        $sut = new RemovePropertyCarbonFootprintService($repository, new PropertyCarbonFootprintAuthorizer());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new RemovePropertyCarbonFootprintRequest($footprint->id()), $this->ownerUser());
    }

    private function ownerUser(): \App\Security\Domain\Model\User\User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
