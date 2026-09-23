<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\Data;

use App\CarbonFootprint\Application\CarbonFootprintReport\Data\CompanyCarbonFootprintReportDataCollector;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use PHPUnit\Framework\TestCase;

class CompanyCarbonFootprintReportDataCollectorTest extends TestCase
{
    use OrganizationalCollectorContextTrait;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->initActivities();
    }

    public function testCollectsTheDataOfTheCompanyWithABreakdownByProperty(): void
    {
        $company = CompanyMother::fromName('Hotelera');
        $companies = CompanyRepositoryMother::inMemory();
        $companies->add($company);

        $propertyOne = PropertyMother::fromNameAndCompanyIdAndId('P1', $company->id(), PropertyId::generate());
        $propertyTwo = PropertyMother::fromNameAndCompanyIdAndId('P2', $company->id(), PropertyId::generate());
        $properties = PropertyRepositoryMother::inMemory();
        $properties->add($propertyOne);
        $properties->add($propertyTwo);

        $emissionFactorGroups = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroups->add(EmissionFactorGroupMother::default());

        $footprint = CompanyCarbonFootprintMother::withScope2Answered((string) $company->id(), (string) CompanyCarbonFootprintId::generate(), 'account-id', 2025);
        $footprint->addActivity($this->combustibleActivity('180')->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: $propertyOne->id()));
        $footprint->addActivity($this->electricityActivity('2500', '2000')->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity, propertyId: $propertyOne->id()));
        $footprint->addActivity($this->electricityActivity('0', null)->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity, propertyId: $propertyTwo->id()));
        $footprint->addActivity($this->roadTransportActivity('1250')->id(), new ActivityPlacement(Scope::Scope1, Source::OwnVehicles));
        $footprint->complete(new Emissions('3930', '3930', '0', '0'));

        $footprints = CompanyCarbonFootprintRepositoryMother::inMemory();
        $footprints->add($footprint);

        $sut = new CompanyCarbonFootprintReportDataCollector(
            $footprints,
            $companies,
            $properties,
            $emissionFactorGroups,
            $this->breakdownBuilder(),
            $this->linesBuilder(),
            new FakeTranslator(),
        );

        $data = $sut->collect(CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Company, (string) $footprint->id()));

        $this->assertInstanceOf(OrganizationalCarbonFootprintReportData::class, $data);
        $this->assertSame(CarbonFootprintReportSubjectType::Company, $data->subjectType());
        $this->assertSame('Hotelera', $data->subjectName());
        $this->assertNull($data->companyName);
        $this->assertSame('3930', $data->totalEmissions()->co2eq());
        $this->assertSame('2000', $data->locationBasedElectricityEmissions->co2eq());

        $this->assertSame(['P1', 'P2', 'Sin asignar a propiedad'], array_column($data->emissionsByProperty, 'name'));
        $this->assertSame([(string) $propertyOne->id(), (string) $propertyTwo->id(), null], array_column($data->emissionsByProperty, 'propertyId'));
        $this->assertSame('2680', $data->emissionsByProperty[0]['emissions']->co2eq());
        $this->assertSame('0', $data->emissionsByProperty[1]['emissions']->co2eq());
        $this->assertSame('1250', $data->emissionsByProperty[2]['emissions']->co2eq());

        $this->assertCount(4, $data->activityLines);
        $this->assertSame(['P1', null, 'P1', 'P2'], array_map(static fn($line) => $line->propertyName, $data->activityLines));
        $this->assertSame([Source::FixedFacilities, Source::OwnVehicles, Source::BuildingElectricity, Source::BuildingElectricity], array_map(static fn($line) => $line->source, $data->activityLines));
        $this->assertSame([], $data->exclusionLines);
    }

    public function testOmitsTheUnassignedRowWhenItHasNoEmissions(): void
    {
        $company = CompanyMother::fromName('Hotelera');
        $companies = CompanyRepositoryMother::inMemory();
        $companies->add($company);

        $property = PropertyMother::fromNameAndCompanyIdAndId('P1', $company->id(), PropertyId::generate());
        $properties = PropertyRepositoryMother::inMemory();
        $properties->add($property);

        $emissionFactorGroups = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroups->add(EmissionFactorGroupMother::default());

        $footprint = CompanyCarbonFootprintMother::withScope2Answered((string) $company->id(), (string) CompanyCarbonFootprintId::generate(), 'account-id', 2025);
        $footprint->addActivity($this->combustibleActivity('180')->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities, propertyId: $property->id()));

        $footprints = CompanyCarbonFootprintRepositoryMother::inMemory();
        $footprints->add($footprint);

        $sut = new CompanyCarbonFootprintReportDataCollector($footprints, $companies, $properties, $emissionFactorGroups, $this->breakdownBuilder(), $this->linesBuilder(), new FakeTranslator());

        $data = $sut->collect(CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Company, (string) $footprint->id()));

        $this->assertInstanceOf(OrganizationalCarbonFootprintReportData::class, $data);
        $this->assertSame(['P1'], array_column($data->emissionsByProperty, 'name'));
    }

    public function testSupportsOnlyCompanies(): void
    {
        $sut = new CompanyCarbonFootprintReportDataCollector(
            CompanyCarbonFootprintRepositoryMother::inMemory(),
            CompanyRepositoryMother::inMemory(),
            PropertyRepositoryMother::inMemory(),
            EmissionFactorGroupRepositoryMother::inMemory(),
            $this->breakdownBuilder(),
            $this->linesBuilder(),
            new FakeTranslator(),
        );

        $this->assertTrue($sut->supports(CarbonFootprintReportSubjectType::Company));
        $this->assertFalse($sut->supports(CarbonFootprintReportSubjectType::Event));
    }
}
