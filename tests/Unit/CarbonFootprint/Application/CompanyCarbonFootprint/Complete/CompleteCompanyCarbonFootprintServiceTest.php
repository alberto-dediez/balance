<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint\Complete;

use App\CarbonFootprint\Application\CarbonFootprint\DerivedSourceEmissionsCalculator;
use App\CarbonFootprint\Application\CarbonFootprint\EmissionsBreakdownBuilder;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Complete\CompleteCompanyCarbonFootprintRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\Complete\CompleteCompanyCarbonFootprintService;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\NonConsolidatedProperty\NonConsolidatedPropertiesSynchronizer;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\NonConsolidatedProperty\NonConsolidatedPropertyEmissionsResolver;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintNonConsolidatedPropertyRecorded;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintNotReadyToCompleteException;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryActivityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMobilityRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOrganizationalEmployeeContributionRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOrganizationalEmployeeParticipationRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
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
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipationMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation\OrganizationalEmployeeParticipationRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CompleteCompanyCarbonFootprintServiceTest extends TestCase
{
    private InMemoryCompanyCarbonFootprintRepository $repository;
    private InMemoryActivityRepository $activityRepository;
    private InMemoryPropertyRepository $propertyRepository;
    private InMemoryPropertyCarbonFootprintRepository $propertyCarbonFootprintRepository;
    private InMemoryOrganizationalEmployeeParticipationRepository $participationRepository;
    private InMemoryOrganizationalEmployeeContributionRepository $contributionRepository;
    private InMemoryMobilityRepository $mobilityRepository;
    private CompleteCompanyCarbonFootprintService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->repository = CompanyCarbonFootprintRepositoryMother::inMemory();
        $this->activityRepository = ActivityRepositoryMother::inMemory();
        $this->participationRepository = OrganizationalEmployeeParticipationRepositoryMother::inMemory();
        $this->contributionRepository = OrganizationalEmployeeContributionRepositoryMother::inMemory();
        $this->mobilityRepository = MobilityRepositoryMother::inMemory();

        $this->propertyRepository = PropertyRepositoryMother::inMemory();
        $this->propertyCarbonFootprintRepository = PropertyCarbonFootprintRepositoryMother::inMemory();
        $breakdownBuilder = new EmissionsBreakdownBuilder($this->activityRepository, new DerivedSourceEmissionsCalculator($this->activityRepository, CountedActivityReferencesMother::create()), CountedActivityReferencesMother::create());

        $this->sut = new CompleteCompanyCarbonFootprintService(
            $this->repository,
            new CompanyCarbonFootprintAuthorizer(),
            $breakdownBuilder,
            SyncOrganizationalEmployeeActivitiesServiceMother::create(
                OrganizationalCarbonFootprintLocatorMother::create(null, $this->repository),
                $this->participationRepository,
                $this->contributionRepository,
                $this->activityRepository,
                $this->mobilityRepository,
            ),
            new NonConsolidatedPropertiesSynchronizer(new NonConsolidatedPropertyEmissionsResolver(
                new CompanyProperties($this->propertyRepository),
                $this->propertyCarbonFootprintRepository,
                $breakdownBuilder,
            )),
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

    public function testRefreshesTheCalculatedEmissionsKeepingTheManualOnesAndAddsThemToTheTotal(): void
    {
        $footprint = CompanyCarbonFootprintMother::withEmployeesAnswered('company-1', (string) CompanyCarbonFootprintId::generate(), 'account-id-test');
        $this->propertyRepository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Franquicia', new CompanyId('company-1'), new PropertyId('franchised-1'), OperatingRegime::Franchised));
        $this->propertyRepository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Arrendada', new CompanyId('company-1'), new PropertyId('leased-out-1'), OperatingRegime::LeasedOut));
        $this->completedPropertyFootprint('franchised-1');

        $footprint->recordNonConsolidatedProperty(new PropertyId('franchised-1'), Source::Franchises, null, new Emissions('999'), null);
        $footprint->recordNonConsolidatedProperty(new PropertyId('leased-out-1'), Source::DownstreamLeasedAssets, null, new Emissions(), '4500');
        $this->repository->add($footprint);

        ($this->sut)(new CompleteCompanyCarbonFootprintRequest($footprint->id()), $this->ownerUser());

        $linesByProperty = [];
        foreach ($footprint->nonConsolidatedPropertyLines() as $line) {
            $linesByProperty[(string) $line->propertyId] = $line;
        }

        $this->assertSame(15.0, (float) $linesByProperty['franchised-1']->calculatedEmissions->co2eq());
        $this->assertNotNull($linesByProperty['franchised-1']->propertyCarbonFootprintId);
        $this->assertSame('4500', $linesByProperty['leased-out-1']->manualCo2eq);
        $this->assertSame(4515.0, (float) $footprint->totalEmissions()->co2eq());
    }

    public function testAnExcludedScope3DoesNotRecordNonConsolidatedProperties(): void
    {
        $footprint = CompanyCarbonFootprintMother::withScope3Excluded('company-1', (string) CompanyCarbonFootprintId::generate(), 'account-id-test');
        $this->propertyRepository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Franquicia', new CompanyId('company-1'), new PropertyId('franchised-1'), OperatingRegime::Franchised));
        $this->completedPropertyFootprint('franchised-1');
        $this->repository->add($footprint);

        ($this->sut)(new CompleteCompanyCarbonFootprintRequest($footprint->id()), $this->ownerUser());

        $this->assertSame([], $footprint->nonConsolidatedPropertyLines());
        $this->assertSame('0', $footprint->totalEmissions()->co2eq());
    }

    private function completedPropertyFootprint(string $propertyId): void
    {
        $propertyFootprint = PropertyCarbonFootprintMother::withEmployeesAnswered($propertyId, (string) PropertyCarbonFootprintId::generate(), 'account-id-test');
        $activity = CombustibleActivityMother::registered(new AccountId('account-id-test'));
        $this->activityRepository->save($activity);
        $propertyFootprint->addActivity($activity->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $propertyFootprint->complete(new Emissions('15'));
        $this->propertyCarbonFootprintRepository->add($propertyFootprint);
    }

    public function testTheTotalIncludesTheEmployeeActivitiesSynchronizedOnCompletion(): void
    {
        $footprint = $this->scope3AnsweredFootprint();
        $this->repository->add($footprint);
        $mobility = MobilityMother::from('Tren', (string) MobilityId::generate());
        $mobility->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Kilometer)->withYear($footprint->year()));
        $this->mobilityRepository->add($mobility);
        $this->participationRepository->add(OrganizationalEmployeeParticipationMother::withTotal(CarbonFootprintReportSubjectType::Company, (string) $footprint->id(), 10, 'account-id-test'));
        $this->contributionRepository->add(OrganizationalEmployeeContributionMother::answered(CarbonFootprintReportSubjectType::Company, (string) $footprint->id(), (string) $mobility->id(), accountId: 'account-id-test'));

        ($this->sut)(new CompleteCompanyCarbonFootprintRequest($footprint->id()), $this->ownerUser());

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
        $footprint = CompanyCarbonFootprintMother::withProvidersAnswered((string) CompanyId::generate(), (string) CompanyCarbonFootprintId::generate(), 'account-id-test');
        $this->repository->add($footprint);
        $mobility = MobilityMother::from('Tren', (string) MobilityId::generate());
        $mobility->setEmissionFactor(ActivityMother::emissionFactor()->withUnit(Unit::Kilometer)->withYear($footprint->year()));
        $this->mobilityRepository->add($mobility);
        $participation = OrganizationalEmployeeParticipationMother::withTotal(CarbonFootprintReportSubjectType::Company, (string) $footprint->id(), 10, 'account-id-test');
        $this->participationRepository->add($participation);
        $this->contributionRepository->add(OrganizationalEmployeeContributionMother::answered(CarbonFootprintReportSubjectType::Company, (string) $footprint->id(), (string) $mobility->id(), accountId: 'account-id-test'));

        try {
            ($this->sut)(new CompleteCompanyCarbonFootprintRequest($footprint->id()), $this->ownerUser());
            $this->fail('Expected the footprint not to be ready to complete');
        } catch (OrganizationalCarbonFootprintNotReadyToCompleteException) {
        }

        $this->assertCount(0, array_filter($footprint->activityReferences(), static fn($reference) => $reference->placement->fromEmployees));
        $this->assertNull($participation->aggregatedAt());
    }

    public function testAFootprintWithoutEmployeesAnsweredDoesNotRecordNonConsolidatedProperties(): void
    {
        $footprint = CompanyCarbonFootprintMother::withProvidersAnswered('company-1', (string) CompanyCarbonFootprintId::generate(), 'account-id-test');
        $this->propertyRepository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Franquicia', new CompanyId('company-1'), new PropertyId('franchised-1'), OperatingRegime::Franchised));
        $this->completedPropertyFootprint('franchised-1');
        $this->repository->add($footprint);

        try {
            ($this->sut)(new CompleteCompanyCarbonFootprintRequest($footprint->id()), $this->ownerUser());
            $this->fail('Expected the footprint not to be ready to complete');
        } catch (OrganizationalCarbonFootprintNotReadyToCompleteException) {
        }

        $this->assertSame([], $footprint->nonConsolidatedPropertyLines());
        $this->assertCount(0, array_filter($footprint->recordedEvents(), static fn($event) => $event instanceof CompanyCarbonFootprintNonConsolidatedPropertyRecorded));
    }

    private function scope3AnsweredFootprint(): CompanyCarbonFootprint
    {
        return CompanyCarbonFootprintMother::withEmployeesAnswered((string) CompanyId::generate(), (string) CompanyCarbonFootprintId::generate(), 'account-id-test');
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id-test'));
    }
}
