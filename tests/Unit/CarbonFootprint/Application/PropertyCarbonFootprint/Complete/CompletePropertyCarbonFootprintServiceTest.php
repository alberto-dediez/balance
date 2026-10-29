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
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintAlreadyCompletedException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintNotReadyToCompleteException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMobilityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOrganizationalEmployeeContributionRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOrganizationalEmployeeParticipationRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution\SyncOrganizationalEmployeeActivitiesServiceMother;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\CountedActivityReferencesMother;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalCarbonFootprintLocatorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\CombustibleActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\PurchaseServiceActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipationMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipationRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CompletePropertyCarbonFootprintServiceTest extends TestCase
{
    private InMemoryPropertyCarbonFootprintRepository $repository;
    private InMemoryActivityRepository $activityRepository;
    private InMemoryOrganizationalEmployeeParticipationRepository $participationRepository;
    private InMemoryOrganizationalEmployeeContributionRepository $contributionRepository;
    private InMemoryMobilityRepository $mobilityRepository;
    private CompletePropertyCarbonFootprintService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = PropertyCarbonFootprintRepositoryMother::inMemory();
        $this->activityRepository = ActivityRepositoryMother::inMemory();
        $this->participationRepository = OrganizationalEmployeeParticipationRepositoryMother::inMemory();
        $this->contributionRepository = OrganizationalEmployeeContributionRepositoryMother::inMemory();
        $this->mobilityRepository = MobilityRepositoryMother::inMemory();

        $this->sut = new CompletePropertyCarbonFootprintService(
            $this->repository,
            new PropertyCarbonFootprintAuthorizer(),
            new EmissionsBreakdownBuilder($this->activityRepository, new DerivedSourceEmissionsCalculator($this->activityRepository, CountedActivityReferencesMother::create()), CountedActivityReferencesMother::create()),
            SyncOrganizationalEmployeeActivitiesServiceMother::create(
                OrganizationalCarbonFootprintLocatorMother::create($this->repository),
                $this->participationRepository,
                $this->contributionRepository,
                $this->activityRepository,
                $this->mobilityRepository,
            ),
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

    public function testTheTotalIncludesTheEmployeeActivitiesSynchronizedOnCompletion(): void
    {
        $footprint = $this->scope3AnsweredFootprint();
        $this->repository->add($footprint);
        $mobility = MobilityMother::from('Tren', (string) MobilityId::generate());
        $mobility->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Kilometer)->withYear($footprint->year()));
        $this->mobilityRepository->add($mobility);
        $this->participationRepository->add(OrganizationalEmployeeParticipationMother::withTotal(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), 10, 'account-id-test'));
        $this->contributionRepository->add(OrganizationalEmployeeContributionMother::answered(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), (string) $mobility->id(), accountId: 'account-id-test'));

        ($this->sut)(new CompletePropertyCarbonFootprintRequest($footprint->id()), $this->ownerUser());

        $this->assertTrue($footprint->isCompleted());
        $employeeActivities = array_filter($footprint->activityReferences(), static fn($reference) => $reference->placement->fromEmployees);
        $this->assertCount(1, $employeeActivities);
        $employeeActivity = $this->activityRepository->findById(array_values($employeeActivities)[0]->activityId);
        $this->assertNotNull($employeeActivity);
        $this->assertSame(55200.0, round($employeeActivity->quantity()->value(), 6));
        $this->assertSame(82800.0, round((float) $footprint->totalEmissions()->co2eq(), 6));
    }

    public function testAFootprintWithoutEmployeesAnsweredDoesNotSynchronizeTheEmployeeActivities(): void
    {
        $footprint = PropertyCarbonFootprintMother::withProvidersAnswered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id-test');
        $this->repository->add($footprint);
        $mobility = MobilityMother::from('Tren', (string) MobilityId::generate());
        $mobility->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Kilometer)->withYear($footprint->year()));
        $this->mobilityRepository->add($mobility);
        $participation = OrganizationalEmployeeParticipationMother::withTotal(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), 10, 'account-id-test');
        $this->participationRepository->add($participation);
        $this->contributionRepository->add(OrganizationalEmployeeContributionMother::answered(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), (string) $mobility->id(), accountId: 'account-id-test'));

        try {
            ($this->sut)(new CompletePropertyCarbonFootprintRequest($footprint->id()), $this->ownerUser());
            $this->fail('Expected the footprint not to be ready to complete');
        } catch (OrganizationalCarbonFootprintNotReadyToCompleteException) {
        }

        $this->assertCount(0, array_filter($footprint->activityReferences(), static fn($reference) => $reference->placement->fromEmployees));
        $this->assertNull($participation->aggregatedAt());
    }

    public function testACompletedFootprintDoesNotSynchronizeTheEmployeeActivities(): void
    {
        $footprint = $this->scope3AnsweredFootprint();
        $footprint->complete(new Emissions());
        $this->repository->add($footprint);
        $mobility = MobilityMother::from('Tren', (string) MobilityId::generate());
        $mobility->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Kilometer)->withYear($footprint->year()));
        $this->mobilityRepository->add($mobility);
        $participation = OrganizationalEmployeeParticipationMother::withTotal(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), 10, 'account-id-test');
        $this->participationRepository->add($participation);
        $this->contributionRepository->add(OrganizationalEmployeeContributionMother::answered(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), (string) $mobility->id(), accountId: 'account-id-test'));

        try {
            ($this->sut)(new CompletePropertyCarbonFootprintRequest($footprint->id()), $this->ownerUser());
            $this->fail('Expected the footprint to be already completed');
        } catch (OrganizationalCarbonFootprintAlreadyCompletedException) {
        }

        $this->assertCount(0, array_filter($footprint->activityReferences(), static fn($reference) => $reference->placement->fromEmployees));
        $this->assertNull($participation->aggregatedAt());
    }

    private function scope3AnsweredFootprint(): PropertyCarbonFootprint
    {
        return PropertyCarbonFootprintMother::withEmployeesAnswered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id-test');
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
