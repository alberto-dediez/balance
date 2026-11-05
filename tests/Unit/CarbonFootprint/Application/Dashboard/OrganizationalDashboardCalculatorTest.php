<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Dashboard;

use App\CarbonFootprint\Application\Dashboard\GetOwnerDashboard\OrganizationalFootprintOverview;
use App\CarbonFootprint\Application\Dashboard\GetOwnerDashboard\PropertyEmissions;
use App\CarbonFootprint\Application\Dashboard\OrganizationalDashboard;
use App\CarbonFootprint\Application\Dashboard\OrganizationalDashboardCalculator;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryMitigationActionRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryCompanyRepository;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\Data\OrganizationalCollectorContextTrait;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use PHPUnit\Framework\TestCase;

final class OrganizationalDashboardCalculatorTest extends TestCase
{
    use OrganizationalCollectorContextTrait;

    private const string ACCOUNT = 'account-1';
    private const string PROPERTY_A = 'property-a';
    private const string PROPERTY_B = 'property-b';
    private const string PROPERTY_C = 'property-c';
    private const string COMPANY = 'company-e';

    private InMemoryPropertyRepository $properties;
    private InMemoryCompanyRepository $companies;
    private InMemoryPropertyCarbonFootprintRepository $propertyFootprints;
    private InMemoryCompanyCarbonFootprintRepository $companyFootprints;
    private InMemoryMitigationActionRepository $mitigationActions;
    private OrganizationalDashboardCalculator $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->initActivities();

        $this->properties = PropertyRepositoryMother::inMemory();
        $this->companies = CompanyRepositoryMother::inMemory();
        $this->propertyFootprints = PropertyCarbonFootprintRepositoryMother::inMemory();
        $this->companyFootprints = CompanyCarbonFootprintRepositoryMother::inMemory();
        $this->mitigationActions = MitigationActionRepositoryMother::inMemory();

        $this->sut = new OrganizationalDashboardCalculator(
            $this->propertyFootprints,
            $this->companyFootprints,
            $this->properties,
            $this->companies,
            $this->mitigationActions,
            $this->breakdownBuilder(),
        );
    }

    public function testCountsAndReferenceYear(): void
    {
        $this->addProperty(self::PROPERTY_A, 'Hotel A');
        $this->addProperty(self::PROPERTY_B, 'Hotel B');
        $this->addProperty(self::PROPERTY_C, 'Hotel C');
        $this->companies->add(CompanyMother::fromNameAndAccountAndId('Hotelera E', self::ACCOUNT, self::COMPANY));

        $this->completedProperty(self::PROPERTY_A, 'p1', 2024, '120000');
        $this->completedProperty(self::PROPERTY_A, 'p2', 2025, '100000');
        $this->completedProperty(self::PROPERTY_B, 'p3', 2025, '50000');
        $this->propertyFootprints->add(PropertyCarbonFootprintMother::withScope2Answered(self::PROPERTY_C, 'p4', self::ACCOUNT, 2025));
        $this->completedCompany('e1', 2025, '160000');

        $dashboard = $this->dashboard();

        $this->assertSame(3, $dashboard->totalProperties);
        $this->assertSame(2, $dashboard->propertiesWithCompletedFootprint);
        $this->assertSame(1, $dashboard->totalCompanies);
        $this->assertSame(1, $dashboard->companiesWithCompletedFootprint);
        $this->assertSame(5, $dashboard->totalFootprints);
        $this->assertSame(4, $dashboard->completedFootprints);
        $this->assertSame(1, $dashboard->inProgressFootprints);
        $this->assertSame(0, $dashboard->footprintsWithMitigationPlan);
        $this->assertSame(2025, $dashboard->referenceYear);
        $this->assertSame(150000.0, $dashboard->referenceYearTotalKg);
        $this->assertSame(2024, $dashboard->previousYear);
        $this->assertNotNull($dashboard->variationVsPreviousYearPercent);
        $this->assertEqualsWithDelta(25.0, $dashboard->variationVsPreviousYearPercent, 0.0001);
        $this->assertSame([2024 => 120000.0, 2025 => 150000.0], $dashboard->emissionsByYearKg);
        $this->assertCount(2, $dashboard->topPropertiesByEmissions);
        $this->assertSame('Hotel A', $dashboard->topPropertiesByEmissions[0]->property->name());
        $this->assertSame(100000.0, $dashboard->topPropertiesByEmissions[0]->co2eq);
        $this->assertSame('Hotel B', $dashboard->topPropertiesByEmissions[1]->property->name());
        $this->assertCount(5, $dashboard->latestFootprints);

        $subjectNames = [];
        foreach ($dashboard->latestFootprints as $overview) {
            $subjectNames[(string) $overview->footprint->id()] = [$overview->subjectType, $overview->subjectName];
        }
        $this->assertSame([CarbonFootprintReportSubjectType::Property, 'Hotel C'], $subjectNames['p4']);
        $this->assertSame([CarbonFootprintReportSubjectType::Company, 'Hotelera E'], $subjectNames['e1']);
    }

    public function testCountsFootprintsWithMitigationActions(): void
    {
        $this->addProperty(self::PROPERTY_A, 'Hotel A');
        $this->addProperty(self::PROPERTY_B, 'Hotel B');
        $this->addProperty(self::PROPERTY_C, 'Hotel C');
        $this->companies->add(CompanyMother::fromNameAndAccountAndId('Hotelera E', self::ACCOUNT, self::COMPANY));

        $this->completedProperty(self::PROPERTY_A, 'p1', 2025, '1000');
        $this->completedProperty(self::PROPERTY_B, 'p2', 2025, '1000');
        $this->completedProperty(self::PROPERTY_C, 'p3', 2025, '1000');
        $this->completedCompany('e1', 2025, '1000');

        $this->mitigationActions->add(MitigationActionMother::manual(carbonFootprintId: 'p1', accountId: self::ACCOUNT));
        $this->mitigationActions->add(MitigationActionMother::manual(carbonFootprintId: 'p1', accountId: self::ACCOUNT));
        $this->mitigationActions->add(MitigationActionMother::manual(carbonFootprintId: 'e1', subjectType: CarbonFootprintReportSubjectType::Company, accountId: self::ACCOUNT));
        $this->mitigationActions->add(MitigationActionMother::manual(carbonFootprintId: 'p2', subjectType: CarbonFootprintReportSubjectType::Company, accountId: self::ACCOUNT));
        $this->mitigationActions->add(MitigationActionMother::manual(carbonFootprintId: 'p2', accountId: 'other-account'));

        $removed = MitigationActionMother::manual(carbonFootprintId: 'p3', accountId: self::ACCOUNT);
        $removed->remove();
        $this->mitigationActions->add($removed);

        $dashboard = $this->dashboard();

        $this->assertSame(2, $dashboard->footprintsWithMitigationPlan);
    }

    public function testLatestFootprintsAreTheFiveMostRecent(): void
    {
        $this->addProperty(self::PROPERTY_A, 'Hotel A');

        foreach (['p1', 'p2', 'p3', 'p4', 'p5', 'p6'] as $id) {
            $this->propertyFootprints->add(PropertyCarbonFootprintMother::from(self::PROPERTY_A, $id, self::ACCOUNT, 2025));
            usleep(1000);
        }

        $dashboard = $this->dashboard();

        $this->assertSame(
            ['p6', 'p5', 'p4', 'p3', 'p2'],
            array_map(static fn(OrganizationalFootprintOverview $overview): string => (string) $overview->footprint->id(), $dashboard->latestFootprints),
        );
    }

    public function testTopPropertiesAreLimitedToFive(): void
    {
        foreach ([1, 2, 3, 4, 5, 6] as $index) {
            $this->addProperty('property-' . $index, 'Hotel ' . $index);
            $this->completedProperty('property-' . $index, 'p' . $index, 2025, (string) ($index * 1000));
        }

        $dashboard = $this->dashboard();

        $this->assertSame(
            ['Hotel 6', 'Hotel 5', 'Hotel 4', 'Hotel 3', 'Hotel 2'],
            array_map(static fn(PropertyEmissions $emissions): string => $emissions->property->name(), $dashboard->topPropertiesByEmissions),
        );
    }

    public function testCompanyFootprintsAreNotSummed(): void
    {
        $this->companies->add(CompanyMother::fromNameAndAccountAndId('Hotelera E', self::ACCOUNT, self::COMPANY));
        $this->completedCompany('e1', 2025, '160000');

        $dashboard = $this->dashboard();

        $this->assertNull($dashboard->referenceYear);
        $this->assertSame(0.0, $dashboard->referenceYearTotalKg);
        $this->assertSame([], $dashboard->referenceYearByScopeKg);
        $this->assertSame([], $dashboard->emissionsByYearKg);
        $this->assertSame([], $dashboard->topPropertiesByEmissions);
        $this->assertSame(1, $dashboard->companiesWithCompletedFootprint);
        $this->assertSame(1, $dashboard->completedFootprints);
    }

    public function testIgnoresRemovedAndNotCompletedFootprints(): void
    {
        $this->addProperty(self::PROPERTY_A, 'Hotel A');
        $removed = $this->completedProperty(self::PROPERTY_A, 'p1', 2025, '9999');
        $removed->remove();
        $this->propertyFootprints->add($removed);
        $this->propertyFootprints->add(PropertyCarbonFootprintMother::from(self::PROPERTY_A, 'p2', self::ACCOUNT, 2025));

        $dashboard = $this->dashboard();

        $this->assertSame(1, $dashboard->totalFootprints);
        $this->assertSame(0, $dashboard->completedFootprints);
        $this->assertSame(1, $dashboard->inProgressFootprints);
        $this->assertSame(0, $dashboard->propertiesWithCompletedFootprint);
        $this->assertNull($dashboard->referenceYear);
        $this->assertSame([], $dashboard->emissionsByYearKg);
    }

    public function testKeepsLatestCompletedFootprintPerPropertyAndYear(): void
    {
        $this->addProperty(self::PROPERTY_A, 'Hotel A');
        $this->completedProperty(self::PROPERTY_A, 'p1', 2025, '1000');
        usleep(1000);
        $this->completedProperty(self::PROPERTY_A, 'p2', 2025, '3000');

        $dashboard = $this->dashboard();

        $this->assertSame(2, $dashboard->completedFootprints);
        $this->assertSame([2025 => 3000.0], $dashboard->emissionsByYearKg);
        $this->assertSame(3000.0, $dashboard->referenceYearTotalKg);
        $this->assertCount(1, $dashboard->topPropertiesByEmissions);
    }

    public function testVariationIsNullWithoutPreviousYear(): void
    {
        $this->addProperty(self::PROPERTY_A, 'Hotel A');
        $this->completedProperty(self::PROPERTY_A, 'p1', 2025, '1000');

        $dashboard = $this->dashboard();

        $this->assertSame(2025, $dashboard->referenceYear);
        $this->assertNull($dashboard->previousYear);
        $this->assertNull($dashboard->variationVsPreviousYearPercent);
    }

    public function testVariationIsNullWhenPreviousTotalIsZero(): void
    {
        $this->addProperty(self::PROPERTY_A, 'Hotel A');
        $this->completedProperty(self::PROPERTY_A, 'p1', 2023, '0');
        $this->completedProperty(self::PROPERTY_A, 'p2', 2025, '1000');

        $dashboard = $this->dashboard();

        $this->assertSame(2023, $dashboard->previousYear);
        $this->assertNull($dashboard->variationVsPreviousYearPercent);
    }

    public function testScopeBreakdownOfReferenceYear(): void
    {
        $this->addProperty(self::PROPERTY_A, 'Hotel A');
        $this->addProperty(self::PROPERTY_B, 'Hotel B');
        $this->completedProperty(self::PROPERTY_A, 'p1', 2024, '7777');

        $withElectricity = PropertyCarbonFootprintMother::withEmployeesAnswered(self::PROPERTY_A, 'p2', self::ACCOUNT, 2025);
        $withElectricity->addActivity($this->combustibleActivity('400')->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $withElectricity->addActivity($this->electricityActivity('250', null)->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));
        $withElectricity->complete(new Emissions('650', '650'));
        $this->propertyFootprints->add($withElectricity);
        $this->completedProperty(self::PROPERTY_B, 'p3', 2025, '100');

        $dashboard = $this->dashboard();

        $this->assertSame(
            [Scope::Scope1->value => 500.0, Scope::Scope2->value => 250.0, Scope::Scope3->value => 0.0],
            $dashboard->referenceYearByScopeKg,
        );
    }

    public function testEmptyAccount(): void
    {
        $dashboard = $this->dashboard();

        $this->assertSame(0, $dashboard->totalProperties);
        $this->assertSame(0, $dashboard->propertiesWithCompletedFootprint);
        $this->assertSame(0, $dashboard->totalCompanies);
        $this->assertSame(0, $dashboard->companiesWithCompletedFootprint);
        $this->assertSame(0, $dashboard->totalFootprints);
        $this->assertSame(0, $dashboard->completedFootprints);
        $this->assertSame(0, $dashboard->inProgressFootprints);
        $this->assertSame(0, $dashboard->footprintsWithMitigationPlan);
        $this->assertNull($dashboard->referenceYear);
        $this->assertSame(0.0, $dashboard->referenceYearTotalKg);
        $this->assertSame([], $dashboard->referenceYearByScopeKg);
        $this->assertNull($dashboard->previousYear);
        $this->assertNull($dashboard->variationVsPreviousYearPercent);
        $this->assertSame([], $dashboard->emissionsByYearKg);
        $this->assertSame([], $dashboard->topPropertiesByEmissions);
        $this->assertSame([], $dashboard->latestFootprints);
    }

    private function dashboard(): OrganizationalDashboard
    {
        return ($this->sut)(new AccountId(self::ACCOUNT));
    }

    private function addProperty(string $id, string $name): void
    {
        $this->properties->add(PropertyMother::fromNameAndAccountAndId($name, self::ACCOUNT, $id));
    }

    private function completedProperty(string $propertyId, string $id, int $year, string $kg): PropertyCarbonFootprint
    {
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered($propertyId, $id, self::ACCOUNT, $year);
        $footprint->addActivity($this->combustibleActivity($kg)->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $footprint->complete(new Emissions($kg, $kg));
        $this->propertyFootprints->add($footprint);

        return $footprint;
    }

    private function completedCompany(string $id, int $year, string $kg): CompanyCarbonFootprint
    {
        $footprint = CompanyCarbonFootprintMother::withEmployeesAnswered(self::COMPANY, $id, self::ACCOUNT, $year);
        $footprint->complete(new Emissions($kg, $kg));
        $this->companyFootprints->add($footprint);

        return $footprint;
    }
}
