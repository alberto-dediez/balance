<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprint\ChangePrecalculatedMode;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\ChangePrecalculatedMode\ChangePropertyCarbonFootprintPrecalculatedModeRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\ChangePrecalculatedMode\ChangePropertyCarbonFootprintPrecalculatedModeService;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintAlreadyCompletedException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ChangePropertyCarbonFootprintPrecalculatedModeServiceTest extends TestCase
{
    private InMemoryPropertyCarbonFootprintRepository $repository;
    private ChangePropertyCarbonFootprintPrecalculatedModeService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = PropertyCarbonFootprintRepositoryMother::inMemory();

        $this->sut = new ChangePropertyCarbonFootprintPrecalculatedModeService($this->repository, new PropertyCarbonFootprintAuthorizer());
    }

    public function testMarksTheFootprintAsPrecalculated(): void
    {
        $footprint = PropertyCarbonFootprintMother::from((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id-test');
        $placement = new ActivityPlacement(Scope::Scope1, Source::FixedFacilities);
        $footprint->addActivity(ActivityId::generate(), $placement);
        $this->repository->add($footprint);

        ($this->sut)(new ChangePropertyCarbonFootprintPrecalculatedModeRequest($footprint->id(), true), $this->ownerUser());

        $this->assertTrue($footprint->isPrecalculated());
        $this->assertCount(0, $footprint->activityIds($placement));
    }

    public function testUnmarksTheFootprintAsPrecalculated(): void
    {
        $footprint = PropertyCarbonFootprintMother::precalculated((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id-test');
        $this->repository->add($footprint);

        ($this->sut)(new ChangePropertyCarbonFootprintPrecalculatedModeRequest($footprint->id(), false), $this->ownerUser());

        $this->assertFalse($footprint->isPrecalculated());
    }

    public function testOtherAccountIsDenied(): void
    {
        $footprint = PropertyCarbonFootprintMother::from((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'other-account-id');
        $this->repository->add($footprint);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new ChangePropertyCarbonFootprintPrecalculatedModeRequest($footprint->id(), true), $this->ownerUser());
    }

    public function testFootprintNotFoundThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new ChangePropertyCarbonFootprintPrecalculatedModeRequest(PropertyCarbonFootprintId::generate(), true), $this->ownerUser());
    }

    public function testCompletedFootprintThrows(): void
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id-test');
        $footprint->complete(new Emissions('30', '30', '0', '0'));
        $this->repository->add($footprint);

        $this->expectException(OrganizationalCarbonFootprintAlreadyCompletedException::class);

        ($this->sut)(new ChangePropertyCarbonFootprintPrecalculatedModeRequest($footprint->id(), true), $this->ownerUser());
    }

    public function testIsHandled(): void
    {
        $this->assertTrue($this->sut->isHandled(new ChangePropertyCarbonFootprintPrecalculatedModeRequest(PropertyCarbonFootprintId::generate(), true)));
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
