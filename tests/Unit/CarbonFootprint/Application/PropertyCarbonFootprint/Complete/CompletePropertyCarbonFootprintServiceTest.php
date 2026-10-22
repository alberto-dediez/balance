<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PropertyCarbonFootprint\Complete;

use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Complete\CompletePropertyCarbonFootprintRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\Complete\CompletePropertyCarbonFootprintService;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintNotReadyToCompleteException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\CombustibleActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\PurchaseServiceActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CompletePropertyCarbonFootprintServiceTest extends TestCase
{
    private InMemoryPropertyCarbonFootprintRepository $repository;
    private InMemoryActivityRepository $activityRepository;
    private CompletePropertyCarbonFootprintService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = PropertyCarbonFootprintRepositoryMother::inMemory();
        $this->activityRepository = ActivityRepositoryMother::inMemory();

        $this->sut = new CompletePropertyCarbonFootprintService(
            $this->repository,
            new PropertyCarbonFootprintAuthorizer(),
            new EmissionsBreakdownBuilder($this->activityRepository, new DerivedSourceEmissionsCalculator($this->activityRepository)),
        );
    }

    public function testCompletesTheFootprintWithTheTotalOfItsActivities(): void
    {
        $footprint = $this->scope3AnsweredFootprint();
        $activity = CombustibleActivityMother::registered(new AccountId('account-id-test'));
        $this->activityRepository->save($activity);
        $footprint->addActivity($activity->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $this->repository->add($footprint);

        ($this->sut)(new CompletePropertyCarbonFootprintRequest($footprint->id()), $this->ownerUser());

        $this->assertTrue($footprint->isCompleted());
        $this->assertSame('15', $footprint->totalEmissions()->co2eq());
    }

    public function testTheTotalIncludesScope3AndTheDerivedFuelAndEnergyEmissions(): void
    {
        $footprint = $this->scope3AnsweredFootprint();
        $combustible = CombustibleActivityMother::registeredWithWellToTank(new AccountId('account-id-test'));
        $purchase = PurchaseServiceActivityMother::registered(new AccountId('account-id-test'));
        $this->activityRepository->save($combustible);
        $this->activityRepository->save($purchase);
        $footprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $footprint->addActivity($purchase->id(), new ActivityPlacement(Scope::Scope3, Source::PurchasedGoodsAndServices));
        $this->repository->add($footprint);

        ($this->sut)(new CompletePropertyCarbonFootprintRequest($footprint->id()), $this->ownerUser());

        $expected = (float) $combustible->emissions()->co2eq() + (float) $purchase->emissions()->co2eq() + (float) $combustible->wellToTankEmissions()->co2eq();
        $this->assertSame($expected, (float) $footprint->totalEmissions()->co2eq());
    }

    public function testAnExcludedScope3IsLeftOutOfTheTotal(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope3Excluded((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id-test');
        $combustible = CombustibleActivityMother::registeredWithWellToTank(new AccountId('account-id-test'));
        $this->activityRepository->save($combustible);
        $footprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $this->repository->add($footprint);

        ($this->sut)(new CompletePropertyCarbonFootprintRequest($footprint->id()), $this->ownerUser());

        $this->assertSame('15', $footprint->totalEmissions()->co2eq());
    }

    public function testScope2AnsweredFootprintThrows(): void
    {
        $footprint = PropertyCarbonFootprintMother::withScope2Answered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id-test');
        $this->repository->add($footprint);

        $this->expectException(OrganizationalCarbonFootprintNotReadyToCompleteException::class);

        ($this->sut)(new CompletePropertyCarbonFootprintRequest($footprint->id()), $this->ownerUser());
    }

    public function testFootprintNotFoundThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new CompletePropertyCarbonFootprintRequest(PropertyCarbonFootprintId::generate()), $this->ownerUser());
    }

    public function testNotReadyFootprintThrows(): void
    {
        $footprint = PropertyCarbonFootprintMother::fromPropertyIdAndAccount((string) PropertyId::generate(), 'account-id-test');
        $this->repository->add($footprint);

        $this->expectException(OrganizationalCarbonFootprintNotReadyToCompleteException::class);

        ($this->sut)(new CompletePropertyCarbonFootprintRequest($footprint->id()), $this->ownerUser());
    }

    public function testUnauthorizedAccessThrows(): void
    {
        $footprint = $this->scope3AnsweredFootprint();
        $this->repository->add($footprint);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new CompletePropertyCarbonFootprintRequest($footprint->id()), UserMother::fromUsernameAndRole('user', Role::User));
    }

    private function scope3AnsweredFootprint(): PropertyCarbonFootprint
    {
        return PropertyCarbonFootprintMother::withScope3Answered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id-test');
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
