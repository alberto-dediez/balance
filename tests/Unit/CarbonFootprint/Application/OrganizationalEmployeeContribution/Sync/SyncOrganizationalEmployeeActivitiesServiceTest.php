<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution\Sync;

use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Sync\SyncOrganizationalEmployeeActivitiesRequest;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Sync\SyncOrganizationalEmployeeActivitiesResponse;
use App\CarbonFootprint\Application\OrganizationalEmployeeContribution\Sync\SyncOrganizationalEmployeeActivitiesService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\Mobility\FactorBasis;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\Unit;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Property\Domain\Model\Company\CompanyId;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionServiceTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution\OrganizationalEmployeeContributionMother;

class SyncOrganizationalEmployeeActivitiesServiceTest extends OrganizationalEmployeeContributionServiceTestCase
{
    private SyncOrganizationalEmployeeActivitiesService $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = $this->syncService();
    }

    public function testCreatesEmployeeActivitiesInTheThreeSections(): void
    {
        $footprint = $this->propertyFootprint();
        $this->participation($footprint, CarbonFootprintReportSubjectType::Property, 40);
        $this->organizingTeamOvernightStay();
        $car = $this->mobility(FactorBasis::PerVehicle, 'Coche');
        $plane = $this->mobility(FactorBasis::PerPassenger, 'Avión');
        $this->answeredContribution($footprint, CarbonFootprintReportSubjectType::Property, $car, $plane, 2);
        $this->answeredContribution($footprint, CarbonFootprintReportSubjectType::Property, $car);

        $response = $this->sync(CarbonFootprintReportSubjectType::Property, (string) $footprint->id());

        $this->assertSame(2, $response->answeredCount);
        $this->assertSame(40, $response->totalEmployees);
        $this->assertNotNull($response->marginOfError);
        $this->assertFalse($response->overnightStayCatalogMissing);
        $this->assertCount(1, $this->employeeActivityIds($footprint, Source::EmployeeCommuting));
        $this->assertCount(2, $this->employeeActivityIds($footprint, Source::BusinessTravel));
        $this->assertSame([220800.0], $this->employeeQuantities($footprint, Source::EmployeeCommuting, Unit::Kilometer));
        $this->assertSame([24000.0], $this->employeeQuantities($footprint, Source::BusinessTravel, Unit::Kilometer));
        $this->assertSame([40.0], $this->employeeQuantities($footprint, Source::BusinessTravel, Unit::Pernoctacion));

        $participation = $this->participationRepository->findByCarbonFootprint(CarbonFootprintReportSubjectType::Property, (string) $footprint->id());
        $this->assertNotNull($participation);
        $this->assertSame(2, $participation->answeredCount());
        $this->assertNotNull($participation->aggregatedAt());
    }

    public function testInACompanyTheActivitiesAreGroupedByWorkplace(): void
    {
        $companyId = CompanyId::generate();
        $hotel = $this->consolidatedProperty($companyId, 'Hotel');
        $franchise = $this->nonConsolidatedProperty($companyId);
        $footprint = $this->companyFootprint($companyId);
        $this->participation($footprint, CarbonFootprintReportSubjectType::Company, 10);
        $train = $this->mobility();
        $this->answeredContribution($footprint, CarbonFootprintReportSubjectType::Company, $train, workplace: $hotel);
        $this->answeredContribution($footprint, CarbonFootprintReportSubjectType::Company, $train);
        $this->answeredContribution($footprint, CarbonFootprintReportSubjectType::Company, $train, workplace: $franchise);

        $this->sync(CarbonFootprintReportSubjectType::Company, (string) $footprint->id());

        $this->assertCount(1, $this->employeeActivityIds($footprint, Source::EmployeeCommuting, $hotel));
        $this->assertCount(1, $this->employeeActivityIds($footprint, Source::EmployeeCommuting));
        $this->assertCount(0, $this->employeeActivityIds($footprint, Source::EmployeeCommuting, $franchise));
        $this->assertSame([18400.0], $this->employeeQuantities($footprint, Source::EmployeeCommuting, Unit::Kilometer, $hotel));
        $this->assertSame([36800.0], $this->employeeQuantities($footprint, Source::EmployeeCommuting, Unit::Kilometer));
    }

    public function testAnExcludedSourceIsLeftOut(): void
    {
        $footprint = $this->propertyFootprint();
        $footprint->excludeSource(Scope::Scope3, Source::EmployeeCommuting, ExclusionReason::NoDataAvailable);
        $this->participation($footprint, CarbonFootprintReportSubjectType::Property, 10);
        $train = $this->mobility();
        $this->answeredContribution($footprint, CarbonFootprintReportSubjectType::Property, $train, $train);

        $response = $this->sync(CarbonFootprintReportSubjectType::Property, (string) $footprint->id());

        $this->assertSame([Source::EmployeeCommuting], $response->excludedSources);
        $this->assertCount(0, $this->employeeActivityIds($footprint, Source::EmployeeCommuting));
        $this->assertCount(1, $this->employeeActivityIds($footprint, Source::BusinessTravel));
        $this->assertSame([12000.0], $this->employeeQuantities($footprint, Source::BusinessTravel, Unit::Kilometer));
    }

    public function testWithoutAnOrganizingTeamOvernightStayTheNightsAreSkipped(): void
    {
        $footprint = $this->propertyFootprint();
        $this->participation($footprint, CarbonFootprintReportSubjectType::Property, 10);
        $train = $this->mobility();
        $this->answeredContribution($footprint, CarbonFootprintReportSubjectType::Property, $train, $train, 3);

        $response = $this->sync(CarbonFootprintReportSubjectType::Property, (string) $footprint->id());

        $this->assertTrue($response->overnightStayCatalogMissing);
        $this->assertCount(1, $this->employeeActivityIds($footprint, Source::BusinessTravel));
        $this->assertSame([12000.0], $this->employeeQuantities($footprint, Source::BusinessTravel, Unit::Kilometer));
        $this->assertSame([], $this->employeeQuantities($footprint, Source::BusinessTravel, Unit::Pernoctacion));
    }

    public function testWithoutAnswersThePreviousEmployeeActivitiesAreRemoved(): void
    {
        $footprint = $this->propertyFootprint();
        $this->participation($footprint, CarbonFootprintReportSubjectType::Property, 10);
        $train = $this->mobility();
        $contribution = $this->answeredContribution($footprint, CarbonFootprintReportSubjectType::Property, $train);
        $this->sync(CarbonFootprintReportSubjectType::Property, (string) $footprint->id());
        $this->assertCount(1, $this->employeeActivityIds($footprint, Source::EmployeeCommuting));

        $contribution->ignore();
        $response = $this->sync(CarbonFootprintReportSubjectType::Property, (string) $footprint->id());

        $this->assertSame(0, $response->answeredCount);
        $this->assertNull($response->marginOfError);
        $this->assertCount(0, $this->employeeActivityIds($footprint, Source::EmployeeCommuting));
    }

    public function testASecondSynchronizationReplacesTheActivitiesWithoutDuplicating(): void
    {
        $footprint = $this->propertyFootprint();
        $this->participation($footprint, CarbonFootprintReportSubjectType::Property, 10);
        $train = $this->mobility();
        $this->answeredContribution($footprint, CarbonFootprintReportSubjectType::Property, $train);
        $this->sync(CarbonFootprintReportSubjectType::Property, (string) $footprint->id());
        $this->assertSame([55200.0], $this->employeeQuantities($footprint, Source::EmployeeCommuting, Unit::Kilometer));

        $this->contributionRepository->add(OrganizationalEmployeeContributionMother::answered(
            subjectType: CarbonFootprintReportSubjectType::Property,
            carbonFootprintId: (string) $footprint->id(),
            commuteMobilityId: (string) $train->id(),
            commuteKm: 24.0,
            accountId: self::ACCOUNT_ID,
        ));
        $this->sync(CarbonFootprintReportSubjectType::Property, (string) $footprint->id());

        $this->assertCount(1, $this->employeeActivityIds($footprint, Source::EmployeeCommuting));
        $this->assertSame([82800.0], $this->employeeQuantities($footprint, Source::EmployeeCommuting, Unit::Kilometer));
    }

    public function testTheOwnerAndProviderActivitiesAreKept(): void
    {
        $footprint = $this->propertyFootprint();
        $ownerActivityId = ActivityId::generate();
        $providerActivityId = ActivityId::generate();
        $footprint->addActivity($ownerActivityId, new ActivityPlacement(Scope::Scope3, Source::EmployeeCommuting));
        $footprint->addActivity($providerActivityId, new ActivityPlacement(Scope::Scope3, Source::EmployeeCommuting, providerId: ProviderId::generate()));
        $this->participation($footprint, CarbonFootprintReportSubjectType::Property, 10);
        $this->answeredContribution($footprint, CarbonFootprintReportSubjectType::Property, $this->mobility());

        $this->sync(CarbonFootprintReportSubjectType::Property, (string) $footprint->id());

        $this->assertCount(1, $footprint->activityIds(new ActivityPlacement(Scope::Scope3, Source::EmployeeCommuting)));
        $this->assertCount(3, array_filter($footprint->activityReferences(), static fn($reference) => Source::EmployeeCommuting === $reference->placement->source));
        $this->assertSame([55200.0], $this->employeeQuantities($footprint, Source::EmployeeCommuting, Unit::Kilometer));
    }

    public function testAMobilityWithoutAFactorForTheYearUsesTheNearestYear(): void
    {
        $footprint = $this->propertyFootprint();
        $this->participation($footprint, CarbonFootprintReportSubjectType::Property, 10);
        $car = $this->mobilityWithFactorsFor([self::YEAR - 2, self::YEAR + 3], FactorBasis::PerVehicle, 'Coche');
        $this->answeredContribution($footprint, CarbonFootprintReportSubjectType::Property, $car);

        $response = $this->sync(CarbonFootprintReportSubjectType::Property, (string) $footprint->id());

        $this->assertSame(['Coche' => self::YEAR - 2], $response->fallbackYears);
        $this->assertSame([], $response->skippedMobilities);
        $activities = $this->employeeActivities($footprint, Source::EmployeeCommuting);
        $this->assertCount(1, $activities);
        $this->assertSame(self::YEAR - 2, $activities[0]->emissionFactor()->year());
        $this->assertSame([55200.0], $this->employeeQuantities($footprint, Source::EmployeeCommuting, Unit::Kilometer));
    }

    public function testAMobilityWithoutAnyFactorIsSkippedAndTheRestIsSynchronized(): void
    {
        $footprint = $this->propertyFootprint();
        $this->participation($footprint, CarbonFootprintReportSubjectType::Property, 10);
        $bike = $this->mobilityWithFactorsFor([], name: 'Patinete');
        $train = $this->mobility();
        $this->answeredContribution($footprint, CarbonFootprintReportSubjectType::Property, $bike, $train);

        $response = $this->sync(CarbonFootprintReportSubjectType::Property, (string) $footprint->id());

        $this->assertSame(['Patinete'], $response->skippedMobilities);
        $this->assertSame([], $response->fallbackYears);
        $this->assertCount(0, $this->employeeActivityIds($footprint, Source::EmployeeCommuting));
        $this->assertSame([12000.0], $this->employeeQuantities($footprint, Source::BusinessTravel, Unit::Kilometer));
    }

    public function testAnUnknownMobilityIsSkipped(): void
    {
        $footprint = $this->propertyFootprint();
        $this->participation($footprint, CarbonFootprintReportSubjectType::Property, 10);
        $unknownMobilityId = (string) MobilityId::generate();
        $this->contributionRepository->add(OrganizationalEmployeeContributionMother::answered(
            subjectType: CarbonFootprintReportSubjectType::Property,
            carbonFootprintId: (string) $footprint->id(),
            commuteMobilityId: $unknownMobilityId,
            tripMobilityId: (string) $this->mobility()->id(),
            accountId: self::ACCOUNT_ID,
        ));

        $response = $this->sync(CarbonFootprintReportSubjectType::Property, (string) $footprint->id());

        $this->assertSame([$unknownMobilityId], $response->skippedMobilities);
        $this->assertCount(0, $this->employeeActivityIds($footprint, Source::EmployeeCommuting));
        $this->assertSame([12000.0], $this->employeeQuantities($footprint, Source::BusinessTravel, Unit::Kilometer));
    }

    public function testTheDefaultOvernightStayWithoutAFactorForTheYearUsesTheNearestYear(): void
    {
        $footprint = $this->propertyFootprint();
        $this->participation($footprint, CarbonFootprintReportSubjectType::Property, 10);
        $this->organizingTeamOvernightStay(self::YEAR - 1);
        $train = $this->mobility();
        $this->answeredContribution($footprint, CarbonFootprintReportSubjectType::Property, $train, $train, 2);

        $response = $this->sync(CarbonFootprintReportSubjectType::Property, (string) $footprint->id());

        $this->assertFalse($response->overnightStayCatalogMissing);
        $this->assertSame(['Hotel' => self::YEAR - 1], $response->fallbackYears);
        $nights = array_values(array_filter(
            $this->employeeActivities($footprint, Source::BusinessTravel),
            static fn($activity): bool => Unit::Pernoctacion === $activity->quantity()->unit(),
        ));
        $this->assertCount(1, $nights);
        $this->assertSame(self::YEAR - 1, $nights[0]->emissionFactor()->year());
        $this->assertSame([20.0], $this->employeeQuantities($footprint, Source::BusinessTravel, Unit::Pernoctacion));
    }

    public function testWithoutParticipationNothingHappens(): void
    {
        $footprint = $this->propertyFootprint();
        $this->answeredContribution($footprint, CarbonFootprintReportSubjectType::Property, $this->mobility());

        $response = $this->sync(CarbonFootprintReportSubjectType::Property, (string) $footprint->id());

        $this->assertSame(0, $response->answeredCount);
        $this->assertNull($response->totalEmployees);
        $this->assertCount(0, $this->employeeActivityIds($footprint, Source::EmployeeCommuting));
    }

    private function sync(CarbonFootprintReportSubjectType $subjectType, string $footprintId): SyncOrganizationalEmployeeActivitiesResponse
    {
        return ($this->sut)(new SyncOrganizationalEmployeeActivitiesRequest($subjectType, $footprintId), $this->ownerUser());
    }
}
