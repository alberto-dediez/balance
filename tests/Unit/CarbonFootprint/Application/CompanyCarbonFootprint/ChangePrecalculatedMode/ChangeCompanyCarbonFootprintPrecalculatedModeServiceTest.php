<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint\ChangePrecalculatedMode;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\ChangePrecalculatedMode\ChangeCompanyCarbonFootprintPrecalculatedModeRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\ChangePrecalculatedMode\ChangeCompanyCarbonFootprintPrecalculatedModeService;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintAlreadyCompletedException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ChangeCompanyCarbonFootprintPrecalculatedModeServiceTest extends TestCase
{
    private InMemoryCompanyCarbonFootprintRepository $repository;
    private ChangeCompanyCarbonFootprintPrecalculatedModeService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = CompanyCarbonFootprintRepositoryMother::inMemory();

        $this->sut = new ChangeCompanyCarbonFootprintPrecalculatedModeService($this->repository, new CompanyCarbonFootprintAuthorizer());
    }

    public function testMarksTheFootprintAsPrecalculated(): void
    {
        $footprint = CompanyCarbonFootprintMother::from((string) CompanyId::generate(), (string) CompanyCarbonFootprintId::generate(), 'account-id-test');
        $placement = new ActivityPlacement(Scope::Scope1, Source::FixedFacilities);
        $footprint->addActivity(ActivityId::generate(), $placement);
        $this->repository->add($footprint);

        ($this->sut)(new ChangeCompanyCarbonFootprintPrecalculatedModeRequest($footprint->id(), true), $this->ownerUser());

        $this->assertTrue($footprint->isPrecalculated());
        $this->assertCount(0, $footprint->activityIds($placement));
    }

    public function testUnmarksTheFootprintAsPrecalculated(): void
    {
        $footprint = CompanyCarbonFootprintMother::precalculated((string) CompanyId::generate(), (string) CompanyCarbonFootprintId::generate(), 'account-id-test');
        $this->repository->add($footprint);

        ($this->sut)(new ChangeCompanyCarbonFootprintPrecalculatedModeRequest($footprint->id(), false), $this->ownerUser());

        $this->assertFalse($footprint->isPrecalculated());
    }

    public function testOtherAccountIsDenied(): void
    {
        $footprint = CompanyCarbonFootprintMother::from((string) CompanyId::generate(), (string) CompanyCarbonFootprintId::generate(), 'other-account-id');
        $this->repository->add($footprint);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new ChangeCompanyCarbonFootprintPrecalculatedModeRequest($footprint->id(), true), $this->ownerUser());
    }

    public function testFootprintNotFoundThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new ChangeCompanyCarbonFootprintPrecalculatedModeRequest(CompanyCarbonFootprintId::generate(), true), $this->ownerUser());
    }

    public function testCompletedFootprintThrows(): void
    {
        $footprint = CompanyCarbonFootprintMother::withEmployeesAnswered((string) CompanyId::generate(), (string) CompanyCarbonFootprintId::generate(), 'account-id-test');
        $footprint->complete(new Emissions('30', '30', '0', '0'));
        $this->repository->add($footprint);

        $this->expectException(OrganizationalCarbonFootprintAlreadyCompletedException::class);

        ($this->sut)(new ChangeCompanyCarbonFootprintPrecalculatedModeRequest($footprint->id(), true), $this->ownerUser());
    }

    public function testIsHandled(): void
    {
        $this->assertTrue($this->sut->isHandled(new ChangeCompanyCarbonFootprintPrecalculatedModeRequest(CompanyCarbonFootprintId::generate(), true)));
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
