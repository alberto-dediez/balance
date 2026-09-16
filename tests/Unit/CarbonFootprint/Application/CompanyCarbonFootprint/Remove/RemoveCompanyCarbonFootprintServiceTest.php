<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint\Remove;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Remove\RemoveCompanyCarbonFootprintRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Remove\RemoveCompanyCarbonFootprintService;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RemoveCompanyCarbonFootprintServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testRemovesTheFootprint(): void
    {
        $repository = CompanyCarbonFootprintRepositoryMother::inMemory();
        $footprint = CompanyCarbonFootprintMother::fromCompanyIdAndAccount((string) CompanyId::generate(), 'account-id-test');
        $repository->add($footprint);

        $sut = new RemoveCompanyCarbonFootprintService($repository, new CompanyCarbonFootprintAuthorizer());

        $sut(new RemoveCompanyCarbonFootprintRequest($footprint->id()), $this->ownerUser());

        $this->assertNull($repository->find($footprint->id()));
        $this->assertTrue($footprint->isRemoved());
    }

    public function testUnknownFootprintThrows(): void
    {
        $sut = new RemoveCompanyCarbonFootprintService(CompanyCarbonFootprintRepositoryMother::inMemory(), new CompanyCarbonFootprintAuthorizer());

        $this->expectException(\InvalidArgumentException::class);

        $sut(new RemoveCompanyCarbonFootprintRequest(\App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId::generate()), $this->ownerUser());
    }

    public function testOtherAccountIsDenied(): void
    {
        $repository = CompanyCarbonFootprintRepositoryMother::inMemory();
        $footprint = CompanyCarbonFootprintMother::fromCompanyIdAndAccount((string) CompanyId::generate(), 'other-account-id');
        $repository->add($footprint);

        $sut = new RemoveCompanyCarbonFootprintService($repository, new CompanyCarbonFootprintAuthorizer());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new RemoveCompanyCarbonFootprintRequest($footprint->id()), $this->ownerUser());
    }

    private function ownerUser(): \App\Security\Domain\Model\User\User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
