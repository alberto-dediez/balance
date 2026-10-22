<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\DerivedSourceLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\NonConsolidatedPropertyLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportActivityLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportExclusionLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportMitigationAction;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportMitigationPlan;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationEmissionSource;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationProjector;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Property\Domain\Model\Property\PropertyId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionMother;

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

    public static function withScope3(CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property): OrganizationalCarbonFootprintReportData
    {
        return CarbonFootprintReportSubjectType::Company === $subjectType
            ? self::build($subjectType, 'Hotelera del Sur', null, null, [
                ['propertyId' => 'property-sol', 'name' => 'Hotel Sol', 'emissions' => new Emissions('7580', '7580', '0', '0')],
                ['propertyId' => null, 'name' => 'Sin asignar a propiedad', 'emissions' => new Emissions('1250', '1250', '0', '0')],
            ], scope3: true)
            : self::build($subjectType, 'Hotel Sol', 'Hotelera del Sur', 'España', null, scope3: true);
    }

    public static function withNonConsolidatedProperties(): OrganizationalCarbonFootprintReportData
    {
        $data = self::withScope3(CarbonFootprintReportSubjectType::Company);

        $franchise = new Emissions('5000', '5000', '0', '0');
        $leasedOut = new Emissions('4500');

        $emissionsBySource = $data->emissionsBySource;
        $emissionsBySource[Source::Franchises->value] = $franchise;
        $emissionsBySource[Source::DownstreamLeasedAssets->value] = $leasedOut;

        $emissionsByScope = $data->emissionsByScope;
        $emissionsByScope[Scope::Scope3->value] = $emissionsByScope[Scope::Scope3->value]->add($franchise)->add($leasedOut);

        $emissionsByScopeAndSource = $data->emissionsByScopeAndSource;
        $emissionsByScopeAndSource[Scope::Scope3->value][Source::Franchises->value] = $franchise;
        $emissionsByScopeAndSource[Scope::Scope3->value][Source::DownstreamLeasedAssets->value] = $leasedOut;

        return new OrganizationalCarbonFootprintReportData(
            subjectType: $data->subjectType,
            subjectName: $data->subjectName,
            companyName: $data->companyName,
            countryName: $data->countryName,
            emissionFactorGroupName: $data->emissionFactorGroupName,
            calculationYear: $data->calculationYear,
            totalEmissions: $data->totalEmissions->add($franchise)->add($leasedOut),
            locationBasedElectricityEmissions: $data->locationBasedElectricityEmissions,
            emissionsBySource: $emissionsBySource,
            emissionsByScope: $emissionsByScope,
            emissionsByScopeAndSource: $emissionsByScopeAndSource,
            emissionsByProperty: [
                ['propertyId' => 'property-franquicia', 'name' => 'Hotel Franquicia', 'emissions' => $franchise],
                ['propertyId' => 'property-arrendada', 'name' => 'Hotel Arrendado', 'emissions' => $leasedOut],
                ...$data->emissionsByProperty,
            ],
            activityLines: $data->activityLines,
            exclusionLines: $data->exclusionLines,
            derivedLines: $data->derivedLines,
            nonConsolidatedPropertyLines: [
                new NonConsolidatedPropertyLine(Source::Franchises, new PropertyId('property-franquicia'), PropertyCarbonFootprintId::generate(), $franchise, null, $franchise),
                new NonConsolidatedPropertyLine(Source::DownstreamLeasedAssets, new PropertyId('property-arrendada'), null, new Emissions(), '4500', $leasedOut),
            ],
        );
    }

    public static function withScope3OnlyInPurchasedGoodsAndServices(?Source $excludedSource = null): OrganizationalCarbonFootprintReportData
    {
        $data = self::withScope3();

        $emissionsByScopeAndSource = $data->emissionsByScopeAndSource;
        foreach (array_keys($emissionsByScopeAndSource[Scope::Scope3->value]) as $sourceValue) {
            if (Source::PurchasedGoodsAndServices->value !== $sourceValue) {
                $emissionsByScopeAndSource[Scope::Scope3->value][$sourceValue] = new Emissions();
            }
        }

        $exclusionLines = $data->exclusionLines;
        if (null !== $excludedSource) {
            $exclusionLines[] = new OrganizationalCarbonFootprintReportExclusionLine(Scope::Scope3, $excludedSource, ExclusionReason::NotSignificant, null);
        }

        return new OrganizationalCarbonFootprintReportData(
            subjectType: $data->subjectType,
            subjectName: $data->subjectName,
            companyName: $data->companyName,
            countryName: $data->countryName,
            emissionFactorGroupName: $data->emissionFactorGroupName,
            calculationYear: $data->calculationYear,
            totalEmissions: $data->totalEmissions,
            locationBasedElectricityEmissions: $data->locationBasedElectricityEmissions,
            emissionsBySource: $data->emissionsBySource,
            emissionsByScope: $data->emissionsByScope,
            emissionsByScopeAndSource: $emissionsByScopeAndSource,
            emissionsByProperty: $data->emissionsByProperty,
            activityLines: array_values(array_filter(
                $data->activityLines,
                static fn(OrganizationalCarbonFootprintReportActivityLine $line): bool => Scope::Scope3 !== $line->scope || Source::PurchasedGoodsAndServices === $line->source,
            )),
            exclusionLines: $exclusionLines,
        );
    }

    public static function withScope3Excluded(CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property): OrganizationalCarbonFootprintReportData
    {
        return CarbonFootprintReportSubjectType::Company === $subjectType
            ? self::build($subjectType, 'Hotelera del Sur', null, null, [
                ['propertyId' => 'property-sol', 'name' => 'Hotel Sol', 'emissions' => new Emissions('2680', '2680', '0', '0')],
                ['propertyId' => null, 'name' => 'Sin asignar a propiedad', 'emissions' => new Emissions('1250', '1250', '0', '0')],
            ], scope3Excluded: true)
            : self::build($subjectType, 'Hotel Sol', 'Hotelera del Sur', 'España', null, scope3Excluded: true);
    }

    public static function withMitigationPlan(
        CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property,
        int $targetYear = 2030,
    ): OrganizationalCarbonFootprintReportData {
        $emissionSources = [
            new MitigationEmissionSource(Scope::Scope1, Source::FixedFacilities, 'natural-gas', 'Gas natural', 180.0),
            new MitigationEmissionSource(Scope::Scope1, Source::OwnVehicles, 'diesel-van', 'Furgoneta diésel', 1250.0),
            new MitigationEmissionSource(Scope::Scope2, Source::BuildingElectricity, 'green-marketer', 'Comercializadora Verde', 2500.0),
        ];

        $boiler = MitigationActionMother::manual(name: 'Caldera de condensación', targetYear: $targetYear);
        $boiler->setEffect(Source::FixedFacilities, 'natural-gas', 'Gas natural', 0.2);
        $solar = MitigationActionMother::manual(name: 'Paneles fotovoltaicos', targetYear: $targetYear);
        $solar->setEffect(Source::BuildingElectricity, 'green-marketer', 'Comercializadora Verde', 0.4);

        $projection = (new MitigationProjector())->project(2025, $emissionSources, [$boiler, $solar]);
        assert(null !== $projection);

        $mitigationPlan = new OrganizationalCarbonFootprintReportMitigationPlan(
            actions: [
                new OrganizationalCarbonFootprintReportMitigationAction('Caldera de condensación', 'Sustituir la caldera de gasóleo', 2025, $targetYear, [
                    ['scope' => Scope::Scope1, 'source' => Source::FixedFacilities, 'itemName' => 'Gas natural', 'reduction' => 0.2],
                ]),
                new OrganizationalCarbonFootprintReportMitigationAction('Paneles fotovoltaicos', '', 2025, $targetYear, [
                    ['scope' => Scope::Scope2, 'source' => Source::BuildingElectricity, 'itemName' => 'Comercializadora Verde', 'reduction' => 0.4],
                ]),
            ],
            projection: $projection,
        );

        return CarbonFootprintReportSubjectType::Company === $subjectType
            ? self::build($subjectType, 'Hotelera del Sur', null, null, [
                ['propertyId' => 'property-sol', 'name' => 'Hotel Sol', 'emissions' => new Emissions('2680', '2680', '0', '0')],
                ['propertyId' => null, 'name' => 'Sin asignar a propiedad', 'emissions' => new Emissions('1250', '1250', '0', '0')],
            ], $mitigationPlan)
            : self::build($subjectType, 'Hotel Sol', 'Hotelera del Sur', 'España', null, $mitigationPlan);
    }

    /** @param list<array{propertyId: ?string, name: string, emissions: Emissions}>|null $emissionsByProperty */
    private static function build(
        CarbonFootprintReportSubjectType $subjectType,
        string $subjectName,
        ?string $companyName,
        ?string $countryName,
        ?array $emissionsByProperty,
        ?OrganizationalCarbonFootprintReportMitigationPlan $mitigationPlan = null,
        bool $scope3 = false,
        bool $scope3Excluded = false,
    ): OrganizationalCarbonFootprintReportData {
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
            Scope::Scope3->value => new Emissions(),
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

        $activityLines = [
            new OrganizationalCarbonFootprintReportActivityLine(Scope::Scope1, Source::FixedFacilities, $isCompany ? 'Hotel Sol' : null, 'Gas natural', ActivityType::Combustible, 100.0, Unit::KWhPCS, '1.8', Unit::KWhPCS, 2025, 'MITECO', '180'),
            new OrganizationalCarbonFootprintReportActivityLine(Scope::Scope1, Source::OwnVehicles, null, 'Furgoneta diésel', ActivityType::Transport, 5000.0, Unit::Kilometer, '0.25', Unit::Kilometer, 2025, 'MITECO', '1250'),
            new OrganizationalCarbonFootprintReportActivityLine(Scope::Scope2, Source::BuildingElectricity, $isCompany ? 'Hotel Sol' : null, 'Comercializadora Verde', ActivityType::Electricity, 10000.0, Unit::KWh, '0.25', Unit::KWh, 2025, 'Comercializadora', '2500', '2000'),
        ];
        $exclusionLines = [
            new OrganizationalCarbonFootprintReportExclusionLine(Scope::Scope1, Source::RefrigerantLeaks, ExclusionReason::NotApplicable, 'Sin equipos de refrigeración'),
        ];
        $derivedLines = [];
        $totalEmissions = new Emissions('3930', '3930', '0', '0');

        if ($scope3) {
            $emissionsBySource[Source::PurchasedGoodsAndServices->value] = new Emissions('4000', '4000', '0', '0');
            $emissionsBySource[Source::BusinessTravel->value] = new Emissions('900', '900', '0', '0');
            $emissionsBySource[Source::FuelAndEnergyRelated->value] = new Emissions('590', '590', '0', '0');
            $emissionsByScope[Scope::Scope3->value] = new Emissions('5490', '5490', '0', '0');
            $emissionsByScopeAndSource[Scope::Scope3->value][Source::PurchasedGoodsAndServices->value] = new Emissions('4000', '4000', '0', '0');
            $emissionsByScopeAndSource[Scope::Scope3->value][Source::BusinessTravel->value] = new Emissions('900', '900', '0', '0');
            $emissionsByScopeAndSource[Scope::Scope3->value][Source::FuelAndEnergyRelated->value] = new Emissions('590', '590', '0', '0');
            $activityLines[] = new OrganizationalCarbonFootprintReportActivityLine(Scope::Scope3, Source::PurchasedGoodsAndServices, $isCompany ? 'Hotel Sol' : null, 'Servicios de limpieza', ActivityType::PurchaseService, 8000.0, Unit::Euro, '0.5', Unit::Euro, 2025, 'EXIOBASE', '4000');
            $activityLines[] = new OrganizationalCarbonFootprintReportActivityLine(Scope::Scope3, Source::BusinessTravel, $isCompany ? 'Hotel Sol' : null, 'Avión corto radio', ActivityType::Mobility, 6000.0, Unit::Kilometer, '0.15', Unit::Kilometer, 2025, 'DEFRA', '900');
            $derivedLines = [
                new DerivedSourceLine(Source::FuelAndEnergyRelated, new ActivityPlacement(Scope::Scope1, Source::FixedFacilities), ActivityType::Combustible, new Emissions('210', '210', '0', '0'), ActivityId::generate()),
                new DerivedSourceLine(Source::FuelAndEnergyRelated, new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity), ActivityType::Electricity, new Emissions('380', '380', '0', '0'), ActivityId::generate()),
            ];
            $totalEmissions = new Emissions('9420', '9420', '0', '0');
        }

        if ($scope3Excluded) {
            array_unshift($exclusionLines, new OrganizationalCarbonFootprintReportExclusionLine(Scope::Scope3, null, ExclusionReason::NoDataAvailable, 'Sin datos de proveedores'));
        }

        return new OrganizationalCarbonFootprintReportData(
            subjectType: $subjectType,
            subjectName: $subjectName,
            companyName: $companyName,
            countryName: $countryName,
            emissionFactorGroupName: 'MITECO 2025',
            calculationYear: 2025,
            totalEmissions: $totalEmissions,
            locationBasedElectricityEmissions: new Emissions('2000', '2000', '0', '0'),
            emissionsBySource: $emissionsBySource,
            emissionsByScope: $emissionsByScope,
            emissionsByScopeAndSource: $emissionsByScopeAndSource,
            emissionsByProperty: $emissionsByProperty ?? [],
            activityLines: $activityLines,
            exclusionLines: $exclusionLines,
            mitigationPlan: $mitigationPlan,
            scope3Excluded: $scope3Excluded,
            scope3ExclusionReason: $scope3Excluded ? ExclusionReason::NoDataAvailable : null,
            scope3ExclusionComment: $scope3Excluded ? 'Sin datos de proveedores' : null,
            derivedLines: $derivedLines,
        );
    }
}
