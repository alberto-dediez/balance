<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\Data;

use App\CarbonFootprint\Application\CarbonFootprintReport\Data\PropertyCarbonFootprintReportDataCollector;
use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivity;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEmissionFactorGroupRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyInfo;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyMother;
use App\Tests\Unit\Property\Domain\Model\Company\CompanyRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Country\CountryMother;
use App\Tests\Unit\Property\Domain\Model\Country\CountryRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use PHPUnit\Framework\TestCase;

class PropertyCarbonFootprintReportDataCollectorTest extends TestCase
{
    use OrganizationalCollectorContextTrait;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->initActivities();
    }

    public function testCollectsTheDataOfTheProperty(): void
    {
        $country = CountryMother::spain();
        $countries = CountryRepositoryMother::inMemory();
        $countries->add($country);

        $company = CompanyMother::fromName('Hotelera');
        $companies = CompanyRepositoryMother::inMemory();
        $companies->add($company);

        $property = PropertyMother::from(PropertyId::generate(), 'Hotel Sol', new AccountId('account-id'), new PropertyInfo(countryId: $country->id()), null, $company->id());
        $properties = PropertyRepositoryMother::inMemory();
        $properties->add($property);

        $emissionFactorGroups = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroups->add(EmissionFactorGroupMother::default());

        $footprint = PropertyCarbonFootprintMother::withProvidersAnswered((string) $property->id(), (string) PropertyCarbonFootprintId::generate(), 'account-id', 2025);
        $footprint->addActivity($this->combustibleActivity('180')->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $footprint->addActivity($this->electricityActivity('2500', '2000')->id(), new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity));
        $footprint->addActivity($this->transportActivity('1250')->id(), new ActivityPlacement(Scope::Scope1, Source::OwnVehicles));
        $footprint->excludeSource(Scope::Scope1, Source::RefrigerantLeaks, ExclusionReason::NotApplicable, 'Sin equipos');
        $footprint->complete(new Emissions('3930', '3930', '0', '0'));

        $footprints = PropertyCarbonFootprintRepositoryMother::inMemory();
        $footprints->add($footprint);

        $sut = new PropertyCarbonFootprintReportDataCollector(
            $footprints,
            $properties,
            $companies,
            $countries,
            $emissionFactorGroups,
            $this->breakdownBuilder(),
            $this->linesBuilder(),
            $this->mitigationPlanBuilder(),
            $this->derivedSourceEmissionsCalculator(),
        );

        $data = $sut->collect(CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Property, (string) $footprint->id()));

        $this->assertInstanceOf(OrganizationalCarbonFootprintReportData::class, $data);
        $this->assertSame(CarbonFootprintReportSubjectType::Property, $data->subjectType());
        $this->assertSame('Hotel Sol', $data->subjectName());
        $this->assertSame('Hotelera', $data->companyName);
        $this->assertSame('España', $data->countryName);
        $this->assertSame(2025, $data->calculationYear());
        $this->assertSame('3930', $data->totalEmissions()->co2eq());
        $this->assertSame('2000', $data->locationBasedElectricityEmissions->co2eq());
        $this->assertSame('3430', $data->locationBasedTotalEmissions->co2eq());
        $this->assertSame(0, $data->electricityActivitiesWithoutLocationBased);
        $this->assertSame('1430', $data->emissionsByScope[Scope::Scope1->value]->co2eq());
        $this->assertSame('2500', $data->emissionsByScope[Scope::Scope2->value]->co2eq());
        $this->assertSame('180', $data->emissionsBySource[Source::FixedFacilities->value]->co2eq());
        $this->assertSame('1250', $data->emissionsByScopeAndSource[Scope::Scope1->value][Source::OwnVehicles->value]->co2eq());
        $this->assertSame([], $data->emissionsByProperty);

        $this->assertCount(3, $data->activityLines);
        $this->assertSame([Source::FixedFacilities, Source::OwnVehicles, Source::BuildingElectricity], array_map(static fn($line) => $line->source, $data->activityLines));
        $this->assertNull($data->activityLines[0]->propertyName);
        $this->assertSame('2000', $data->activityLines[2]->locationBasedEmissionsCo2eq);
        $this->assertNull($data->activityLines[0]->locationBasedEmissionsCo2eq);

        $this->assertCount(1, $data->exclusionLines);
        $this->assertSame(Source::RefrigerantLeaks, $data->exclusionLines[0]->source);
        $this->assertSame('Sin equipos', $data->exclusionLines[0]->comment);
        $this->assertNull($data->mitigationPlan);
    }

    public function testIncludesTheMitigationPlan(): void
    {
        $property = PropertyMother::from(PropertyId::generate(), 'Hotel Sol', new AccountId('account-id'), new PropertyInfo(), null, null);
        $properties = PropertyRepositoryMother::inMemory();
        $properties->add($property);

        $emissionFactorGroups = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroups->add(EmissionFactorGroupMother::default());

        $footprint = PropertyCarbonFootprintMother::withProvidersAnswered((string) $property->id(), (string) PropertyCarbonFootprintId::generate(), 'account-id', 2025);
        $activity = $this->combustibleActivity('1000');
        assert($activity instanceof CombustibleActivity);
        $footprint->addActivity($activity->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $footprint->complete(new Emissions('1000', '1000', '0', '0'));

        $footprints = PropertyCarbonFootprintRepositoryMother::inMemory();
        $footprints->add($footprint);

        $action = MitigationActionMother::withEffect(Source::FixedFacilities, (string) $activity->combustibleId(), 0.2, 2025, 2030, (string) $footprint->id());
        $action->translate('Efficient boiler', 'Replace it', Locale::English);
        $this->mitigationActionRepository->add($action);

        $sut = new PropertyCarbonFootprintReportDataCollector(
            $footprints,
            $properties,
            CompanyRepositoryMother::inMemory(),
            CountryRepositoryMother::inMemory(),
            $emissionFactorGroups,
            $this->breakdownBuilder(),
            $this->linesBuilder(),
            $this->mitigationPlanBuilder(),
            $this->derivedSourceEmissionsCalculator(),
        );

        $data = $sut->collect(CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Property, (string) $footprint->id()));

        $this->assertInstanceOf(OrganizationalCarbonFootprintReportData::class, $data);
        $this->assertNotNull($data->mitigationPlan);
        $this->assertCount(1, $data->mitigationPlan->actions);
        $this->assertSame('action-name', $data->mitigationPlan->actions[0]->name);
        $this->assertEqualsWithDelta(800.0, $data->mitigationPlan->projection->totalByYear[2030], 0.001);
    }

    public function testIncludesTheDerivedLinesOfScope3(): void
    {
        $property = PropertyMother::from(PropertyId::generate(), 'Hotel Sol', new AccountId('account-id'), new PropertyInfo(), null, null);
        $properties = PropertyRepositoryMother::inMemory();
        $properties->add($property);

        $emissionFactorGroups = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroups->add(EmissionFactorGroupMother::default());

        $footprint = PropertyCarbonFootprintMother::withScope2Answered((string) $property->id(), (string) PropertyCarbonFootprintId::generate(), 'account-id', 2025);
        $footprint->addActivity($this->combustibleActivityWithWellToTank('1000', '210')->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $footprint->answerScope(Scope::Scope3);

        $footprints = PropertyCarbonFootprintRepositoryMother::inMemory();
        $footprints->add($footprint);

        $data = $this->collectorFor($footprints, $properties, $emissionFactorGroups)->collect(CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Property, (string) $footprint->id()));

        $this->assertInstanceOf(OrganizationalCarbonFootprintReportData::class, $data);
        $this->assertFalse($data->scope3Excluded);
        $this->assertNull($data->scope3ExclusionReason);
        $this->assertCount(1, $data->derivedLines);
        $this->assertSame('210', $data->derivedLines[0]->emissions->co2eq());
        $this->assertSame('210', $data->emissionsByScope[Scope::Scope3->value]->co2eq());
    }

    public function testIncludesTheScope3Exclusion(): void
    {
        $property = PropertyMother::from(PropertyId::generate(), 'Hotel Sol', new AccountId('account-id'), new PropertyInfo(), null, null);
        $properties = PropertyRepositoryMother::inMemory();
        $properties->add($property);

        $emissionFactorGroups = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroups->add(EmissionFactorGroupMother::default());

        $footprint = PropertyCarbonFootprintMother::withScope3Excluded((string) $property->id(), (string) PropertyCarbonFootprintId::generate(), 'account-id', 2025, comment: 'Sin datos');

        $footprints = PropertyCarbonFootprintRepositoryMother::inMemory();
        $footprints->add($footprint);

        $data = $this->collectorFor($footprints, $properties, $emissionFactorGroups)->collect(CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Property, (string) $footprint->id()));

        $this->assertInstanceOf(OrganizationalCarbonFootprintReportData::class, $data);
        $this->assertTrue($data->scope3Excluded);
        $this->assertSame(ExclusionReason::NoDataAvailable, $data->scope3ExclusionReason);
        $this->assertSame('Sin datos', $data->scope3ExclusionComment);
        $this->assertSame([], $data->derivedLines);
        $this->assertSame(Scope::Scope3, $data->exclusionLines[0]->scope);
        $this->assertNull($data->exclusionLines[0]->source);
    }

    public function testSupportsOnlyProperties(): void
    {
        $sut = new PropertyCarbonFootprintReportDataCollector(
            PropertyCarbonFootprintRepositoryMother::inMemory(),
            PropertyRepositoryMother::inMemory(),
            CompanyRepositoryMother::inMemory(),
            CountryRepositoryMother::inMemory(),
            EmissionFactorGroupRepositoryMother::inMemory(),
            $this->breakdownBuilder(),
            $this->linesBuilder(),
            $this->mitigationPlanBuilder(),
            $this->derivedSourceEmissionsCalculator(),
        );

        $this->assertTrue($sut->supports(CarbonFootprintReportSubjectType::Property));
        $this->assertFalse($sut->supports(CarbonFootprintReportSubjectType::Company));
    }

    public function testThrowsWhenTheFootprintDoesNotExist(): void
    {
        $sut = new PropertyCarbonFootprintReportDataCollector(
            PropertyCarbonFootprintRepositoryMother::inMemory(),
            PropertyRepositoryMother::inMemory(),
            CompanyRepositoryMother::inMemory(),
            CountryRepositoryMother::inMemory(),
            EmissionFactorGroupRepositoryMother::inMemory(),
            $this->breakdownBuilder(),
            $this->linesBuilder(),
            $this->mitigationPlanBuilder(),
            $this->derivedSourceEmissionsCalculator(),
        );

        $this->expectException(\RuntimeException::class);

        $sut->collect(CarbonFootprintReportMother::registeredFor(CarbonFootprintReportSubjectType::Property, (string) PropertyCarbonFootprintId::generate()));
    }

    private function collectorFor(
        InMemoryPropertyCarbonFootprintRepository $footprints,
        InMemoryPropertyRepository $properties,
        InMemoryEmissionFactorGroupRepository $emissionFactorGroups,
    ): PropertyCarbonFootprintReportDataCollector {
        return new PropertyCarbonFootprintReportDataCollector(
            $footprints,
            $properties,
            CompanyRepositoryMother::inMemory(),
            CountryRepositoryMother::inMemory(),
            $emissionFactorGroups,
            $this->breakdownBuilder(),
            $this->linesBuilder(),
            $this->mitigationPlanBuilder(),
            $this->derivedSourceEmissionsCalculator(),
        );
    }
}
