<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportActivityLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportExclusionLine;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\Unit;

class OrganizationalCarbonFootprintReportDataMother
{
    public static function forProperty(): OrganizationalCarbonFootprintReportData
    {
        return self::build(CarbonFootprintReportSubjectType::Property, 'Hotel Sol', 'Hotelera del Sur', 'España', null);
    }

    public static function forCompanyWithTwoProperties(): OrganizationalCarbonFootprintReportData
    {
        return self::build(CarbonFootprintReportSubjectType::Company, 'Hotelera del Sur', null, null, [
            ['propertyId' => 'property-sol', 'name' => 'Hotel Sol', 'emissions' => new Emissions('2680', '2680', '0', '0')],
            ['propertyId' => 'property-luna', 'name' => 'Hotel Luna', 'emissions' => new Emissions('0', '0', '0', '0')],
            ['propertyId' => null, 'name' => 'Sin asignar a propiedad', 'emissions' => new Emissions('1250', '1250', '0', '0')],
        ]);
    }

    /** @param list<array{propertyId: ?string, name: string, emissions: Emissions}>|null $emissionsByProperty */
    private static function build(CarbonFootprintReportSubjectType $subjectType, string $subjectName, ?string $companyName, ?string $countryName, ?array $emissionsByProperty): OrganizationalCarbonFootprintReportData
    {
        $emissionsBySource = [];
        foreach (Source::cases() as $source) {
            $emissionsBySource[$source->value] = new Emissions();
        }
        $emissionsBySource[Source::FixedFacilities->value] = new Emissions('180', '180', '0', '0');
        $emissionsBySource[Source::OwnVehicles->value] = new Emissions('1250', '1250', '0', '0');
        $emissionsBySource[Source::BuildingElectricity->value] = new Emissions('2500', '2500', '0', '0');

        $emissionsByScope = [
            Scope::Scope1->value => new Emissions('1430', '1430', '0', '0'),
            Scope::Scope2->value => new Emissions('2500', '2500', '0', '0'),
        ];

        $emissionsByScopeAndSource = [];
        foreach (Scope::cases() as $scope) {
            $emissionsByScopeAndSource[$scope->value] = [];
            foreach (Source::cases() as $source) {
                $emissionsByScopeAndSource[$scope->value][$source->value] = new Emissions();
            }
        }
        $emissionsByScopeAndSource[Scope::Scope1->value][Source::FixedFacilities->value] = new Emissions('180', '180', '0', '0');
        $emissionsByScopeAndSource[Scope::Scope1->value][Source::OwnVehicles->value] = new Emissions('1250', '1250', '0', '0');
        $emissionsByScopeAndSource[Scope::Scope2->value][Source::BuildingElectricity->value] = new Emissions('2500', '2500', '0', '0');

        $isCompany = CarbonFootprintReportSubjectType::Company === $subjectType;

        return new OrganizationalCarbonFootprintReportData(
            subjectType: $subjectType,
            subjectName: $subjectName,
            companyName: $companyName,
            countryName: $countryName,
            emissionFactorGroupName: 'MITECO 2025',
            calculationYear: 2025,
            totalEmissions: new Emissions('3930', '3930', '0', '0'),
            locationBasedElectricityEmissions: new Emissions('2000', '2000', '0', '0'),
            emissionsBySource: $emissionsBySource,
            emissionsByScope: $emissionsByScope,
            emissionsByScopeAndSource: $emissionsByScopeAndSource,
            emissionsByProperty: $emissionsByProperty ?? [],
            activityLines: [
                new OrganizationalCarbonFootprintReportActivityLine(Scope::Scope1, Source::FixedFacilities, $isCompany ? 'Hotel Sol' : null, 'Gas natural', ActivityType::Combustible, 100.0, Unit::KWhPCS, '1.8', Unit::KWhPCS, 2025, 'MITECO', '180'),
                new OrganizationalCarbonFootprintReportActivityLine(Scope::Scope1, Source::OwnVehicles, null, 'Furgoneta diésel', ActivityType::RoadTransport, 5000.0, Unit::Kilometer, '0.25', Unit::Kilometer, 2025, 'MITECO', '1250'),
                new OrganizationalCarbonFootprintReportActivityLine(Scope::Scope2, Source::BuildingElectricity, $isCompany ? 'Hotel Sol' : null, 'Comercializadora Verde', ActivityType::Electricity, 10000.0, Unit::KWh, '0.25', Unit::KWh, 2025, 'Comercializadora', '2500', '2000'),
            ],
            exclusionLines: [
                new OrganizationalCarbonFootprintReportExclusionLine(Scope::Scope1, Source::RefrigerantLeaks, ExclusionReason::NotApplicable, 'Sin equipos de refrigeración'),
            ],
        );
    }
}
