<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintKpi;

use App\CarbonFootprint\Application\BaseYearDeclaration\Declare\DeclareBaseYearRequest;
use App\CarbonFootprint\Application\BaseYearDeclaration\Declare\DeclareBaseYearService;
use App\CarbonFootprint\Application\CarbonFootprintKpi\CarbonFootprintKpiAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintKpi\GetDashboard\GetCarbonFootprintKpiDashboardRequest;
use App\CarbonFootprint\Application\CarbonFootprintKpi\GetDashboard\GetCarbonFootprintKpiDashboardResponse;
use App\CarbonFootprint\Application\CarbonFootprintKpi\GetDashboard\GetCarbonFootprintKpiDashboardService;
use App\CarbonFootprint\Application\CarbonFootprintKpi\KpiSubjectResolver;
use App\CarbonFootprint\Application\CarbonFootprintKpi\SubjectFootprints;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityDenominators;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicatorsCalculator;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\InterannualVariationDecomposer;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryBaseYearDeclarationRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemorySectorBenchmarkRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Domain\Model\Property\PropertyType;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclarationMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclarationRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmarkMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\SectorBenchmark\SectorBenchmarkRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

final class GetCarbonFootprintKpiDashboardServiceTest extends TestCase
{
    use KpiContextTrait;

    private const string PROPERTY = 'property-1';
    private const string ACCOUNT = 'account-1';

    private InMemoryPropertyRepository $properties;
    private InMemoryCompanyRepository $companies;
    private InMemoryPropertyCarbonFootprintRepository $propertyFootprints;
    private InMemoryCompanyCarbonFootprintRepository $companyFootprints;
    private InMemoryBaseYearDeclarationRepository $declarations;
    private InMemorySectorBenchmarkRepository $benchmarks;
    private GetCarbonFootprintKpiDashboardService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->initKpiContext();

        $this->properties = PropertyRepositoryMother::inMemory();
        $this->properties->add(PropertyMother::from(new PropertyId(self::PROPERTY), 'Hotel Sol', new AccountId(self::ACCOUNT), new PropertyInfo(propertyType: PropertyType::Hotel)));
        $this->companies = CompanyRepositoryMother::inMemory();
        $this->propertyFootprints = PropertyCarbonFootprintRepositoryMother::inMemory();
        $this->companyFootprints = CompanyCarbonFootprintRepositoryMother::inMemory();
        $this->declarations = BaseYearDeclarationRepositoryMother::inMemory();
        $this->benchmarks = SectorBenchmarkRepositoryMother::inMemory();

        $this->sut = new GetCarbonFootprintKpiDashboardService(
            new KpiSubjectResolver($this->properties, $this->companies),
            new SubjectFootprints($this->propertyFootprints, $this->companyFootprints),
            $this->inventoryResultsBuilder($this->properties),
            $this->breakdownBuilder(),
            new IntensityIndicatorsCalculator(),
            new InterannualVariationDecomposer(),
            $this->declarations,
            $this->benchmarks,
            new CompanyProperties($this->properties),
            $this->propertyFootprints,
            new CarbonFootprintKpiAuthorizer(),
        );
    }

    public function testWithoutCompletedFootprintsHasNoData(): void
    {
        $this->propertyFootprints->add(PropertyCarbonFootprintMother::from(self::PROPERTY, 'f-1', self::ACCOUNT, 2025));

        $dashboard = $this->dashboard();

        $this->assertFalse($dashboard->hasData);
        $this->assertNull($dashboard->inventory);
        $this->assertSame([], $dashboard->series);
    }

    public function testTwoCompletedYearsBuildTheSeriesAndTheVariation(): void
    {
        $this->completed('f-1', 2024, '1000', new IntensityDenominators(stays: 400));
        $this->completed('f-2', 2025, '1200', new IntensityDenominators(stays: 400, builtAreaM2: '100.00'));

        $dashboard = $this->dashboard();

        $this->assertTrue($dashboard->hasData);
        $this->assertSame(2025, $dashboard->selectedYear);
        $this->assertSame([2024, 2025], $dashboard->availableYears);
        $this->assertCount(2, $dashboard->series);
        $this->assertSame(1200.0, $dashboard->inventory?->totalKg);
        $this->assertSame(1200.0, $dashboard->inventory->scopeKg(Scope::Scope1));
        $this->assertSame([IntensityIndicator::PerStay, IntensityIndicator::PerSquareMeter], array_map(static fn($value) => $value->indicator, $dashboard->indicators));
        $this->assertSame(3.0, $dashboard->indicators[0]->kgCo2eqPerUnit);
        $this->assertNotNull($dashboard->variationVsPrevious);
        $this->assertSame(2024, $dashboard->variationVsPrevious->fromYear);
        $this->assertEqualsWithDelta(200.0, $dashboard->variationVsPrevious->delta, 0.0001);
        $this->assertNull($dashboard->variationVsPrevious->activityEffect);
        $this->assertNull($dashboard->variationVsBaseYear);
    }

    public function testRequestedYearWithoutCompletedFootprintFallsBackToTheLatest(): void
    {
        $this->completed('f-1', 2024, '1000');
        $this->completed('f-2', 2025, '1200');

        $dashboard = $this->dashboard(year: 2022);

        $this->assertSame(2025, $dashboard->selectedYear);
    }

    public function testRemovedFootprintOfTheSameYearIsIgnored(): void
    {
        $removed = $this->completed('f-1', 2025, '9999');
        $removed->remove();
        $this->completed('f-2', 2025, '1200');

        $dashboard = $this->dashboard();

        $this->assertCount(1, $dashboard->series);
        $this->assertSame(1200.0, $dashboard->inventory?->totalKg);
    }

    public function testBaseYearIsMarkedAndComparedWithActivityEffect(): void
    {
        $this->completed('f-1', 2023, '1000', new IntensityDenominators(stays: 500));
        $this->completed('f-2', 2024, '1100', new IntensityDenominators(stays: 500));
        $this->completed('f-3', 2025, '1200', new IntensityDenominators(stays: 600));
        $this->declarations->add(BaseYearDeclarationMother::declared(self::PROPERTY, year: 2023, total: '1000', primaryIndicator: IntensityIndicator::PerStay));

        $dashboard = $this->dashboard();

        $this->assertTrue($dashboard->series[0]->isBaseYear);
        $this->assertFalse($dashboard->series[2]->isBaseYear);
        $this->assertFalse($dashboard->baseYearAlert);
        $this->assertNotNull($dashboard->variationVsBaseYear);
        $this->assertSame(2023, $dashboard->variationVsBaseYear->fromYear);
        $this->assertEqualsWithDelta(200.0, $dashboard->variationVsBaseYear->activityEffect ?? 0.0, 0.0001);
        $this->assertEqualsWithDelta(
            $dashboard->variationVsBaseYear->delta,
            ($dashboard->variationVsBaseYear->activityEffect ?? 0.0) + $dashboard->variationVsBaseYear->factorEffect + $dashboard->variationVsBaseYear->efficiencyEffect,
            0.0001,
        );
    }

    public function testAlertsWhenTheBaseYearTotalChangedAboveTheThreshold(): void
    {
        $this->completed('f-1', 2024, '1100');
        $this->declarations->add(BaseYearDeclarationMother::declared(self::PROPERTY, year: 2024, total: '1000', threshold: '5'));

        $dashboard = $this->dashboard();

        $this->assertTrue($dashboard->baseYearAlert);
        $this->assertSame(1100.0, $dashboard->baseYearCurrentTotalKg);
    }

    public function testTotalsComeFromTheFixedTotalAndBreakdownsFromTheRecalculation(): void
    {
        $this->completed('f-1', 2024, '1000', new IntensityDenominators(stays: 500), fixedKg: '1050');
        $this->completed('f-2', 2025, '1200', new IntensityDenominators(stays: 500), fixedKg: '1300');

        $dashboard = $this->dashboard();

        $this->assertSame(1300.0, $dashboard->inventory?->totalKg);
        $this->assertSame(1200.0, $dashboard->inventory->scopeKg(Scope::Scope1));
        $this->assertSame(2.6, $dashboard->indicators[0]->kgCo2eqPerUnit);
        $this->assertSame(1050.0, $dashboard->series[0]->totalKg);
        $this->assertSame(1000.0, $dashboard->series[0]->byScopeKg[Scope::Scope1->value]);
        $this->assertSame(2.1, $dashboard->series[0]->indicators[IntensityIndicator::PerStay->value]->kgCo2eqPerUnit);
        $this->assertNotNull($dashboard->variationVsPrevious);
        $this->assertSame(1050.0, $dashboard->variationVsPrevious->fromTotal);
        $this->assertSame(1300.0, $dashboard->variationVsPrevious->toTotal);
        $this->assertEqualsWithDelta(250.0, $dashboard->variationVsPrevious->delta, 0.0001);
        $this->assertEqualsWithDelta(
            $dashboard->variationVsPrevious->delta,
            ($dashboard->variationVsPrevious->activityEffect ?? 0.0) + $dashboard->variationVsPrevious->factorEffect + $dashboard->variationVsPrevious->efficiencyEffect,
            0.0001,
        );
    }

    public function testExposesTheDriftBetweenTheFixedAndTheRecalculatedTotal(): void
    {
        $this->completed('f-1', 2025, '1200', fixedKg: '1300');

        $inventory = $this->dashboard()->inventory;

        $this->assertNotNull($inventory);
        $this->assertSame(1300.0, $inventory->totalKg);
        $this->assertSame(1200.0, $inventory->recalculatedTotalKg);
        $this->assertTrue($inventory->hasTotalDrift());
        $this->assertEqualsWithDelta(100.0, $inventory->totalDriftKg(), 0.0001);
    }

    public function testWithoutDriftTheFixedAndTheRecalculatedTotalMatch(): void
    {
        $this->completed('f-1', 2025, '1200');

        $inventory = $this->dashboard()->inventory;

        $this->assertNotNull($inventory);
        $this->assertSame(1200.0, $inventory->recalculatedTotalKg);
        $this->assertFalse($inventory->hasTotalDrift());
    }

    public function testRedeclaringTheBaseYearWithARecalculatedTotalDifferentFromTheFixedOneClearsTheAlert(): void
    {
        $this->completed('f-1', 2024, '1100', fixedKg: '1000');
        $this->declarations->add(BaseYearDeclarationMother::declared(self::PROPERTY, year: 2024, total: '900', threshold: '5'));
        $this->assertTrue($this->dashboard()->baseYearAlert);

        $this->declareBaseYear(2024);
        $dashboard = $this->dashboard();

        $this->assertFalse($dashboard->baseYearAlert);
        $this->assertSame(1000.0, $dashboard->baseYearCurrentTotalKg);
    }

    public function testAlertsWhenTheBaseYearFootprintIsReopenedAndCompletedWithAnotherTotal(): void
    {
        $footprint = $this->completed('f-1', 2024, '1000');
        $this->declareBaseYear(2024);
        $this->assertFalse($this->dashboard()->baseYearAlert);

        $footprint->reopen();
        $footprint->complete(new Emissions('1100', '1100'));
        $this->propertyFootprints->add($footprint);
        $dashboard = $this->dashboard();

        $this->assertTrue($dashboard->baseYearAlert);
        $this->assertSame(1100.0, $dashboard->baseYearCurrentTotalKg);
    }

    public function testCompanyComparesItsPropertiesWithData(): void
    {
        $companyId = new CompanyId('company-1');
        $this->companies->add(CompanyMother::from('Hotelera', 'company-1', 'B1', self::ACCOUNT));
        $this->properties->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Con dato', $companyId, new PropertyId('p-a'), OperatingRegime::Owned));
        $this->properties->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Sin denominador', $companyId, new PropertyId('p-b'), OperatingRegime::Franchised));
        $this->properties->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Sin huella', $companyId, new PropertyId('p-c'), OperatingRegime::Owned));

        $withData = PropertyCarbonFootprintMother::withEmployeesAnswered('p-a', 'pf-a', self::ACCOUNT, 2025);
        $withData->declareIntensityDenominators(new IntensityDenominators(stays: 100));
        $withData->complete(new Emissions('500', '500'));
        $this->propertyFootprints->add($withData);
        $withoutDenominator = PropertyCarbonFootprintMother::withEmployeesAnswered('p-b', 'pf-b', self::ACCOUNT, 2025);
        $withoutDenominator->complete(new Emissions('300', '300'));
        $this->propertyFootprints->add($withoutDenominator);

        $companyFootprint = CompanyCarbonFootprintMother::withEmployeesAnswered('company-1', 'cf-1', self::ACCOUNT, 2025);
        $companyFootprint->declareIntensityDenominators(new IntensityDenominators(stays: 100));
        $companyFootprint->complete(new Emissions('800', '800'));
        $this->companyFootprints->add($companyFootprint);

        $dashboard = ($this->sut)(new GetCarbonFootprintKpiDashboardRequest(CarbonFootprintReportSubjectType::Company, 'company-1'), $this->owner());

        $this->assertSame(IntensityIndicator::PerStay, $dashboard->comparisonIndicator);
        $this->assertCount(1, $dashboard->propertyComparison);
        $this->assertSame('Con dato', $dashboard->propertyComparison[0]->name);
        $this->assertSame(5.0, $dashboard->propertyComparison[0]->intensity);
        $reasons = [];
        foreach ($dashboard->propertiesWithoutData as $row) {
            $reasons[$row['property']->name()] = $row['reason'];
        }
        $this->assertSame([
            'Sin denominador' => GetCarbonFootprintKpiDashboardResponse::NO_DENOMINATOR,
            'Sin huella' => GetCarbonFootprintKpiDashboardResponse::NO_COMPLETED_FOOTPRINT,
        ], $reasons);
    }

    public function testBenchmarkIsReturnedOnlyWhenTheSegmentExists(): void
    {
        $this->completed('f-1', 2025, '1200', new IntensityDenominators(stays: 400, builtAreaM2: '100.00'));
        $this->benchmarks->add(SectorBenchmarkMother::computed(PropertyType::Hotel->value, 2025, IntensityIndicator::PerStay, 3.5));

        $dashboard = $this->dashboard();

        $this->assertSame([IntensityIndicator::PerStay->value], array_keys($dashboard->benchmarks));
        $this->assertSame('3.5000', $dashboard->benchmarks[IntensityIndicator::PerStay->value]->median());
    }

    public function testDualReportingIsInactiveInPrecalculatedFootprints(): void
    {
        $footprint = PropertyCarbonFootprintMother::precalculated(self::PROPERTY, 'f-1', self::ACCOUNT, 2025);
        $footprint->addActivity($this->electricityActivity('2500', '2000')->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));
        $footprint->answerScope(Scope::Scope1);
        $footprint->answerScope(Scope::Scope2);
        $footprint->answerScope(Scope::Scope3);
        $footprint->answerProviders();
        $footprint->answerEmployees();
        $footprint->complete(new Emissions('2500', '2500'));
        $this->propertyFootprints->add($footprint);

        $dashboard = $this->dashboard();

        $this->assertFalse($dashboard->inventory?->dualReportingActive);
    }

    public function testDualReportingIsActiveWithLocationBasedElectricity(): void
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered(self::PROPERTY, 'f-1', self::ACCOUNT, 2025);
        $footprint->addActivity($this->electricityActivity('2500', '2000')->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));
        $footprint->complete(new Emissions('2500', '2500'));
        $this->propertyFootprints->add($footprint);

        $dashboard = $this->dashboard();

        $this->assertTrue($dashboard->inventory?->dualReportingActive);
        $this->assertSame(2000.0, $dashboard->inventory->scope2LocationKg);
        $this->assertSame(2500.0, $dashboard->inventory->scope2MarketKg);
    }

    public function testSubjectOfAnotherAccountIsDenied(): void
    {
        $this->completed('f-1', 2025, '1200');

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(
            new GetCarbonFootprintKpiDashboardRequest(CarbonFootprintReportSubjectType::Property, self::PROPERTY),
            UserMother::fromUsernameAndRoleAndAccount('other', Role::Owner, new AccountId('account-2')),
        );
    }

    private function dashboard(?int $year = null): GetCarbonFootprintKpiDashboardResponse
    {
        return ($this->sut)(new GetCarbonFootprintKpiDashboardRequest(CarbonFootprintReportSubjectType::Property, self::PROPERTY, $year), $this->owner());
    }

    private function declareBaseYear(int $year): void
    {
        $service = new DeclareBaseYearService(
            new KpiSubjectResolver($this->properties, $this->companies),
            new SubjectFootprints($this->propertyFootprints, $this->companyFootprints),
            $this->declarations,
            new CarbonFootprintKpiAuthorizer(),
        );

        $service(new DeclareBaseYearRequest(
            subjectType: CarbonFootprintReportSubjectType::Property,
            subjectId: self::PROPERTY,
            year: $year,
            justification: 'Primer año con inventario completo',
            recalculationThresholdPercent: '5',
        ), $this->owner());
    }

    private function completed(string $id, int $year, string $kg, ?IntensityDenominators $denominators = null, ?string $fixedKg = null): PropertyCarbonFootprint
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered(self::PROPERTY, $id, self::ACCOUNT, $year);
        $footprint->addActivity($this->combustibleActivity($kg)->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));

        if (null !== $denominators) {
            $footprint->declareIntensityDenominators($denominators);
        }

        $footprint->complete(new Emissions($fixedKg ?? $kg, $fixedKg ?? $kg));
        $this->propertyFootprints->add($footprint);

        return $footprint;
    }

    private function owner(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId(self::ACCOUNT));
    }
}
