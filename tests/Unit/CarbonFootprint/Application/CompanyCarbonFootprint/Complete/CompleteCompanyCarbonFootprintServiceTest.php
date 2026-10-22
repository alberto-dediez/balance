<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint\Complete;

use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Complete\CompleteCompanyCarbonFootprintRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Complete\CompleteCompanyCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintNotReadyToCompleteException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\CombustibleActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\PurchaseServiceActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CompleteCompanyCarbonFootprintServiceTest extends TestCase
{
    private InMemoryCompanyCarbonFootprintRepository $repository;
    private InMemoryActivityRepository $activityRepository;
    private CompleteCompanyCarbonFootprintService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = CompanyCarbonFootprintRepositoryMother::inMemory();
        $this->activityRepository = ActivityRepositoryMother::inMemory();

        $this->sut = new CompleteCompanyCarbonFootprintService(
            $this->repository,
            new CompanyCarbonFootprintAuthorizer(),
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

        ($this->sut)(new CompleteCompanyCarbonFootprintRequest($footprint->id()), $this->ownerUser());

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

        ($this->sut)(new CompleteCompanyCarbonFootprintRequest($footprint->id()), $this->ownerUser());

        $expected = (float) $combustible->emissions()->co2eq() + (float) $purchase->emissions()->co2eq() + (float) $combustible->wellToTankEmissions()->co2eq();
        $this->assertSame($expected, (float) $footprint->totalEmissions()->co2eq());
    }

    public function testAnExcludedScope3IsLeftOutOfTheTotal(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope3Excluded((string) CompanyId::generate(), (string) CompanyCarbonFootprintId::generate(), 'account-id-test');
        $combustible = CombustibleActivityMother::registeredWithWellToTank(new AccountId('account-id-test'));
        $this->activityRepository->save($combustible);
        $footprint->addActivity($combustible->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $this->repository->add($footprint);

        ($this->sut)(new CompleteCompanyCarbonFootprintRequest($footprint->id()), $this->ownerUser());

        $this->assertSame('15', $footprint->totalEmissions()->co2eq());
    }

    public function testScope2AnsweredFootprintThrows(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope2Answered((string) CompanyId::generate(), (string) CompanyCarbonFootprintId::generate(), 'account-id-test');
        $this->repository->add($footprint);

        $this->expectException(OrganizationalCarbonFootprintNotReadyToCompleteException::class);

        ($this->sut)(new CompleteCompanyCarbonFootprintRequest($footprint->id()), $this->ownerUser());
    }

    public function testFootprintNotFoundThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->sut)(new CompleteCompanyCarbonFootprintRequest(CompanyCarbonFootprintId::generate()), $this->ownerUser());
    }

    public function testNotReadyFootprintThrows(): void
    {
        $footprint = CompanyCarbonFootprintMother::fromCompanyIdAndAccount((string) CompanyId::generate(), 'account-id-test');
        $this->repository->add($footprint);

        $this->expectException(OrganizationalCarbonFootprintNotReadyToCompleteException::class);

        ($this->sut)(new CompleteCompanyCarbonFootprintRequest($footprint->id()), $this->ownerUser());
    }

    public function testUnauthorizedAccessThrows(): void
    {
        $footprint = $this->scope3AnsweredFootprint();
        $this->repository->add($footprint);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new CompleteCompanyCarbonFootprintRequest($footprint->id()), UserMother::fromUsernameAndRole('user', Role::User));
    }

    private function scope3AnsweredFootprint(): CompanyCarbonFootprint
    {
        return CompanyCarbonFootprintMother::withScope3Answered((string) CompanyId::generate(), (string) CompanyCarbonFootprintId::generate(), 'account-id-test');
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
