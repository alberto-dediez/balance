<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\DerivedSourceLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\NonConsolidatedPropertyLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CalculationMethod;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportActivityLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportBaseYear;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportBoundaryLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportEmployeeSurvey;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportExclusionLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportMaterialityAnnex;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportMitigationAction;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportMitigationPlan;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportProviderLine;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationEmissionSource;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationProjector;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\ProviderContributionMode;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStatus;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\PropertyId;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionMother;

class OrganizationalCarbonFootprintReportDataMother
{
    public static function forProperty(): OrganizationalCarbonFootprintReportData
    {
        return self::build(CarbonFootprintReportSubjectType::Property, 'Hotel Sol', 'Hotelera del Sur', 'España', null);
    }

    public static function precalculated(): OrganizationalCarbonFootprintReportData
    {
        return self::build(CarbonFootprintReportSubjectType::Property, 'Hotel Sol', 'Hotelera del Sur', 'España', null, precalculated: true);
    }

    public static function withElectricityWithoutLocationBasedResult(): OrganizationalCarbonFootprintReportData
    {
        return self::build(CarbonFootprintReportSubjectType::Property, 'Hotel Sol', 'Hotelera del Sur', 'España', null, electricityActivitiesWithoutLocationBased: 1);
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

    public static function withNonConsolidatedProperties(bool $franchiseFromOperatorContribution = false): OrganizationalCarbonFootprintReportData
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
            locationBasedTotalEmissions: $data->locationBasedTotalEmissions->add($franchise)->add($leasedOut),
            electricityActivitiesWithoutLocationBased: $data->electricityActivitiesWithoutLocationBased,
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
                new NonConsolidatedPropertyLine(Source::Franchises, new PropertyId('property-franquicia'), $franchiseFromOperatorContribution ? null : PropertyCarbonFootprintId::generate(), $franchiseFromOperatorContribution ? OperatorContributionId::generate() : null, $franchise, null, $franchise),
                new NonConsolidatedPropertyLine(Source::DownstreamLeasedAssets, new PropertyId('property-arrendada'), null, null, new Emissions(), '4500', $leasedOut),
            ],
            gwpCh4: $data->gwpCh4,
            gwpN2O: $data->gwpN2O,
        );
    }

    public static function forCompanyWithBoundaryLines(?Source $excludedSource = null, bool $scope3Excluded = false): OrganizationalCarbonFootprintReportData
    {
        $data = self::withNonConsolidatedProperties();

        $exclusionLines = $data->exclusionLines;
        if (null !== $excludedSource) {
            $exclusionLines[] = new OrganizationalCarbonFootprintReportExclusionLine(Scope::Scope3, $excludedSource, ExclusionReason::NotSignificant, null);
        }
        if ($scope3Excluded) {
            array_unshift($exclusionLines, new OrganizationalCarbonFootprintReportExclusionLine(Scope::Scope3, null, ExclusionReason::NoDataAvailable, 'Sin datos de proveedores'));
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
            locationBasedTotalEmissions: $data->locationBasedTotalEmissions,
            electricityActivitiesWithoutLocationBased: $data->electricityActivitiesWithoutLocationBased,
            emissionsBySource: $data->emissionsBySource,
            emissionsByScope: $data->emissionsByScope,
            emissionsByScopeAndSource: $data->emissionsByScopeAndSource,
            emissionsByProperty: $data->emissionsByProperty,
            activityLines: $data->activityLines,
            exclusionLines: $exclusionLines,
            scope3Excluded: $scope3Excluded,
            scope3ExclusionReason: $scope3Excluded ? ExclusionReason::NoDataAvailable : null,
            scope3ExclusionComment: $scope3Excluded ? 'Sin datos de proveedores' : null,
            derivedLines: $data->derivedLines,
            nonConsolidatedPropertyLines: $data->nonConsolidatedPropertyLines,
            gwpCh4: $data->gwpCh4,
            gwpN2O: $data->gwpN2O,
            boundaryLines: [
                new OrganizationalCarbonFootprintReportBoundaryLine('Hotel Arrendado', OperatingRegime::LeasedOut, false, Source::DownstreamLeasedAssets),
                new OrganizationalCarbonFootprintReportBoundaryLine('Hotel Franquicia', OperatingRegime::Franchised, false, Source::Franchises),
                new OrganizationalCarbonFootprintReportBoundaryLine('Hotel Luna', OperatingRegime::Managed, true, null),
                new OrganizationalCarbonFootprintReportBoundaryLine('Hotel Sol', OperatingRegime::Owned, true, null),
            ],
        );
    }

    public static function withMixedMethods(): OrganizationalCarbonFootprintReportData
    {
        $data = self::withScope3();

        $spend = new Emissions('3000', '3000', '0', '0');
        $supplier = new Emissions('1200', '1200', '0', '0');
        $commuting = new Emissions('900', '900', '0', '0');
        $upstreamTransport = new Emissions('2000', '2000', '0', '0');
        $operationsWaste = new Emissions('500', '500', '0', '0');
        $purchased = $spend->add($supplier);
        $scope3 = $purchased->add($commuting)->add($upstreamTransport)->add($operationsWaste)->add(new Emissions('590', '590', '0', '0'));

        $emissionsBySource = $data->emissionsBySource;
        $emissionsBySource[Source::PurchasedGoodsAndServices->value] = $purchased;
        $emissionsBySource[Source::BusinessTravel->value] = new Emissions();
        $emissionsBySource[Source::EmployeeCommuting->value] = $commuting;
        $emissionsBySource[Source::UpstreamTransport->value] = $upstreamTransport;
        $emissionsBySource[Source::OperationsWaste->value] = $operationsWaste;

        $emissionsByScope = $data->emissionsByScope;
        $emissionsByScope[Scope::Scope3->value] = $scope3;

        $emissionsByScopeAndSource = $data->emissionsByScopeAndSource;
        $emissionsByScopeAndSource[Scope::Scope3->value][Source::PurchasedGoodsAndServices->value] = $purchased;
        $emissionsByScopeAndSource[Scope::Scope3->value][Source::BusinessTravel->value] = new Emissions();
        $emissionsByScopeAndSource[Scope::Scope3->value][Source::EmployeeCommuting->value] = $commuting;
        $emissionsByScopeAndSource[Scope::Scope3->value][Source::UpstreamTransport->value] = $upstreamTransport;
        $emissionsByScopeAndSource[Scope::Scope3->value][Source::OperationsWaste->value] = $operationsWaste;

        $activityLines = array_values(array_filter(
            $data->activityLines,
            static fn(OrganizationalCarbonFootprintReportActivityLine $line): bool => Scope::Scope3 !== $line->scope,
        ));
        $activityLines[] = new OrganizationalCarbonFootprintReportActivityLine(Scope::Scope3, Source::PurchasedGoodsAndServices, null, 'Servicios de limpieza', ActivityType::PurchaseService, CalculationMethod::SpendBased, 6000.0, Unit::Euro, '0.5', Unit::Euro, 2025, 'EXIOBASE', '3000');
        $activityLines[] = new OrganizationalCarbonFootprintReportActivityLine(Scope::Scope3, Source::PurchasedGoodsAndServices, null, 'Lavandería Norte · Lavandería 2025', ActivityType::ReportedEmissions, CalculationMethod::SupplierSpecific, 1200.0, Unit::Kilogram, '1', Unit::Kilogram, 2025, null, '1200');
        $activityLines[] = new OrganizationalCarbonFootprintReportActivityLine(Scope::Scope3, Source::EmployeeCommuting, null, 'Empleados · Coche diésel', ActivityType::Mobility, CalculationMethod::EmployeeSurveyExtrapolation, 4500.0, Unit::Kilometer, '0.2', Unit::Kilometer, 2024, 'DEFRA', '900');
        $activityLines[] = new OrganizationalCarbonFootprintReportActivityLine(Scope::Scope3, Source::UpstreamTransport, null, 'Camión rígido', ActivityType::Transport, CalculationMethod::DistanceBased, 4000.0, Unit::TonneKilometre, '0.5', Unit::TonneKilometre, 2025, 'GLEC', '2000');
        $activityLines[] = new OrganizationalCarbonFootprintReportActivityLine(Scope::Scope3, Source::OperationsWaste, null, 'Residuos orgánicos', ActivityType::Waste, CalculationMethod::WasteTypeSpecific, 1000.0, Unit::Kilogram, '0.5', Unit::Kilogram, 2025, 'Ecoinvent', '500');

        return new OrganizationalCarbonFootprintReportData(
            subjectType: $data->subjectType,
            subjectName: $data->subjectName,
            companyName: $data->companyName,
            countryName: $data->countryName,
            emissionFactorGroupName: $data->emissionFactorGroupName,
            calculationYear: $data->calculationYear,
            totalEmissions: new Emissions('12120', '12120', '0', '0'),
            locationBasedElectricityEmissions: $data->locationBasedElectricityEmissions,
            locationBasedTotalEmissions: new Emissions('11620', '11620', '0', '0'),
            electricityActivitiesWithoutLocationBased: $data->electricityActivitiesWithoutLocationBased,
            emissionsBySource: $emissionsBySource,
            emissionsByScope: $emissionsByScope,
            emissionsByScopeAndSource: $emissionsByScopeAndSource,
            emissionsByProperty: $data->emissionsByProperty,
            activityLines: $activityLines,
            exclusionLines: $data->exclusionLines,
            derivedLines: $data->derivedLines,
            gwpCh4: $data->gwpCh4,
            gwpN2O: $data->gwpN2O,
            operatingRegime: $data->operatingRegime,
            employeeSurvey: new OrganizationalCarbonFootprintReportEmployeeSurvey(40, 25, ConfidenceLevel::NinetyFive, 0.065),
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
            locationBasedTotalEmissions: $data->locationBasedTotalEmissions,
            electricityActivitiesWithoutLocationBased: $data->electricityActivitiesWithoutLocationBased,
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

    public static function withMaterialityAssessment(CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property): OrganizationalCarbonFootprintReportData
    {
        return self::withMaterialityAnnex(self::withScope3($subjectType), OrganizationalCarbonFootprintReportMaterialityAnnexMother::complete());
    }

    public static function withMitigationPlanAndMaterialityAssessment(CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property): OrganizationalCarbonFootprintReportData
    {
        return self::withMaterialityAnnex(self::withMitigationPlan($subjectType), OrganizationalCarbonFootprintReportMaterialityAnnexMother::complete());
    }

    public static function withMaterialityAssessmentWithoutExclusions(CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property): OrganizationalCarbonFootprintReportData
    {
        return self::withMaterialityAnnex(self::withScope3($subjectType), new OrganizationalCarbonFootprintReportMaterialityAnnex(MaterialityAssessmentSnapshotMother::completed(), false, [], []));
    }

    public static function withMaterialityOverride(CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Company): OrganizationalCarbonFootprintReportData
    {
        return self::withMaterialityAnnex(self::withScope3($subjectType), OrganizationalCarbonFootprintReportMaterialityAnnexMother::withOverride());
    }

    public static function withScope3ExcludedAndMaterialityDecisions(CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property): OrganizationalCarbonFootprintReportData
    {
        return self::withMaterialityAnnex(self::withScope3Excluded($subjectType), OrganizationalCarbonFootprintReportMaterialityAnnexMother::stageExcluded());
    }

    public static function withReopenedMaterialityAssessment(CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property): OrganizationalCarbonFootprintReportData
    {
        return self::withMaterialityAnnex(self::withScope3($subjectType), OrganizationalCarbonFootprintReportMaterialityAnnexMother::reopened());
    }

    public static function withMaterialityDecisionsWithoutAssessment(CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property): OrganizationalCarbonFootprintReportData
    {
        return self::withMaterialityAnnex(self::withScope3($subjectType), OrganizationalCarbonFootprintReportMaterialityAnnexMother::decisionsWithoutAssessment());
    }

    public static function withoutMaterialityAssessment(CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property): OrganizationalCarbonFootprintReportData
    {
        return self::withMaterialityAnnex(self::withScope3($subjectType), OrganizationalCarbonFootprintReportMaterialityAnnexMother::withoutAssessment());
    }

    public static function withMaterialityAssessmentCompletedWithoutVerdicts(CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property): OrganizationalCarbonFootprintReportData
    {
        return self::withMaterialityAnnex(self::withScope3($subjectType), OrganizationalCarbonFootprintReportMaterialityAnnexMother::completedWithoutVerdicts());
    }

    public static function withMaterialityAssessmentCompletedWithoutScope3Topics(CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property): OrganizationalCarbonFootprintReportData
    {
        return self::withMaterialityAnnex(self::withScope3($subjectType), OrganizationalCarbonFootprintReportMaterialityAnnexMother::completedWithoutScope3Topics());
    }

    public static function withMaterialityAssessmentWithoutHighMagnitudeRule(CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property): OrganizationalCarbonFootprintReportData
    {
        return self::withMaterialityAnnex(self::withScope3($subjectType), OrganizationalCarbonFootprintReportMaterialityAnnexMother::withoutHighMagnitudeRule());
    }

    public static function withBaseYear(OrganizationalCarbonFootprintReportData $data, ?OrganizationalCarbonFootprintReportBaseYear $baseYear): OrganizationalCarbonFootprintReportData
    {
        return self::copy($data, ['baseYear' => $baseYear]);
    }

    public static function baseYear(int $year = 2023, string $baseYearTotalKgCo2eq = '8000', string $recalculationThresholdPercent = '5'): OrganizationalCarbonFootprintReportBaseYear
    {
        return new OrganizationalCarbonFootprintReportBaseYear($year, $baseYearTotalKgCo2eq, $recalculationThresholdPercent, 'Primer año con datos completos');
    }

    /** @param list<OrganizationalCarbonFootprintReportProviderLine>|null $providerLines */
    public static function withProviderLines(?OrganizationalCarbonFootprintReportData $data = null, ?array $providerLines = null): OrganizationalCarbonFootprintReportData
    {
        $data ??= self::withScope3();
        $isCompany = CarbonFootprintReportSubjectType::Company === $data->subjectType;

        return self::copy($data, ['providerLines' => $providerLines ?? [
            new OrganizationalCarbonFootprintReportProviderLine('Lavandería Norte', $isCompany ? 'Hotel Sol' : null, [Source::PurchasedGoodsAndServices], ProviderContributionMode::Manual, ProviderContributionStatus::Answered, new \DateTimeImmutable('2026-02-10'), '1098'),
            new OrganizationalCarbonFootprintReportProviderLine('Transportes Sur', null, [Source::UpstreamTransport, Source::DownstreamTransport], ProviderContributionMode::Requested, ProviderContributionStatus::Pending, null, '0'),
            new OrganizationalCarbonFootprintReportProviderLine('Catering Este', null, [Source::PurchasedGoodsAndServices], ProviderContributionMode::Requested, ProviderContributionStatus::Ignored, null, '0'),
        ]]);
    }

    /** @param list<OrganizationalCarbonFootprintReportActivityLine> $activityLines */
    public static function withActivityLines(OrganizationalCarbonFootprintReportData $data, array $activityLines): OrganizationalCarbonFootprintReportData
    {
        return self::copy($data, ['activityLines' => $activityLines]);
    }

    public static function withoutActivityIn(OrganizationalCarbonFootprintReportData $data, Scope $scope, Source $source): OrganizationalCarbonFootprintReportData
    {
        $emissionsByScopeAndSource = $data->emissionsByScopeAndSource;
        $emissionsByScopeAndSource[$scope->value][$source->value] = new Emissions();

        return self::copy($data, [
            'emissionsByScopeAndSource' => $emissionsByScopeAndSource,
            'activityLines' => array_values(array_filter($data->activityLines, static fn(OrganizationalCarbonFootprintReportActivityLine $line): bool => $line->scope !== $scope || $line->source !== $source)),
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private static function copy(OrganizationalCarbonFootprintReportData $data, array $overrides): OrganizationalCarbonFootprintReportData
    {
        return new OrganizationalCarbonFootprintReportData(...array_merge(get_object_vars($data), $overrides));
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
        int $electricityActivitiesWithoutLocationBased = 0,
        bool $precalculated = false,
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
            new OrganizationalCarbonFootprintReportActivityLine(Scope::Scope1, Source::FixedFacilities, $isCompany ? 'Hotel Sol' : null, 'Gas natural', ActivityType::Combustible, CalculationMethod::ActivityBased, 100.0, Unit::KWhPCS, '1.8', Unit::KWhPCS, 2025, 'MITECO', '180'),
            new OrganizationalCarbonFootprintReportActivityLine(Scope::Scope1, Source::OwnVehicles, null, 'Furgoneta diésel', ActivityType::Transport, CalculationMethod::ActivityBased, 5000.0, Unit::Kilometer, '0.25', Unit::Kilometer, 2025, 'MITECO', '1250'),
            new OrganizationalCarbonFootprintReportActivityLine(Scope::Scope2, Source::BuildingElectricity, $isCompany ? 'Hotel Sol' : null, 'Comercializadora Verde', ActivityType::Electricity, CalculationMethod::ActivityBased, 10000.0, Unit::KWh, '0.25', Unit::KWh, 2025, 'Comercializadora', '2500', '2000'),
        ];
        $exclusionLines = [
            new OrganizationalCarbonFootprintReportExclusionLine(Scope::Scope1, Source::RefrigerantLeaks, ExclusionReason::NotApplicable, 'Sin equipos de refrigeración'),
        ];
        $derivedLines = [];
        $totalEmissions = new Emissions('3930', '3930', '0', '0');
        $locationBasedTotalEmissions = new Emissions('3430', '3430', '0', '0');

        if ($scope3) {
            $emissionsBySource[Source::PurchasedGoodsAndServices->value] = new Emissions('4000', '4000', '0', '0');
            $emissionsBySource[Source::BusinessTravel->value] = new Emissions('900', '900', '0', '0');
            $emissionsBySource[Source::FuelAndEnergyRelated->value] = new Emissions('590', '590', '0', '0');
            $emissionsByScope[Scope::Scope3->value] = new Emissions('5490', '5490', '0', '0');
            $emissionsByScopeAndSource[Scope::Scope3->value][Source::PurchasedGoodsAndServices->value] = new Emissions('4000', '4000', '0', '0');
            $emissionsByScopeAndSource[Scope::Scope3->value][Source::BusinessTravel->value] = new Emissions('900', '900', '0', '0');
            $emissionsByScopeAndSource[Scope::Scope3->value][Source::FuelAndEnergyRelated->value] = new Emissions('590', '590', '0', '0');
            $activityLines[] = new OrganizationalCarbonFootprintReportActivityLine(Scope::Scope3, Source::PurchasedGoodsAndServices, $isCompany ? 'Hotel Sol' : null, 'Servicios de limpieza', ActivityType::PurchaseService, CalculationMethod::SpendBased, 8000.0, Unit::Euro, '0.5', Unit::Euro, 2025, 'EXIOBASE', '4000');
            $activityLines[] = new OrganizationalCarbonFootprintReportActivityLine(Scope::Scope3, Source::BusinessTravel, $isCompany ? 'Hotel Sol' : null, 'Avión corto radio', ActivityType::Mobility, CalculationMethod::DistanceBased, 6000.0, Unit::Kilometer, '0.15', Unit::Kilometer, 2025, 'DEFRA', '900');
            $derivedLines = [
                new DerivedSourceLine(Source::FuelAndEnergyRelated, new ActivityPlacement(Scope::Scope1, Source::FixedFacilities), ActivityType::Combustible, new Emissions('210', '210', '0', '0'), ActivityId::generate(), 100.0, Unit::KWhPCS, '2.1', Unit::KWhPCS, 2024, 'DEFRA'),
                new DerivedSourceLine(Source::FuelAndEnergyRelated, new ActivityPlacement(Scope::Scope2, Source::BuildingElectricity), ActivityType::Electricity, new Emissions('380', '380', '0', '0'), ActivityId::generate(), 10000.0, Unit::KWh, '0.038', Unit::KWh, 2025, 'DEFRA'),
            ];
            $totalEmissions = new Emissions('9420', '9420', '0', '0');
            $locationBasedTotalEmissions = new Emissions('8920', '8920', '0', '0');
        }

        $locationBasedElectricityEmissions = new Emissions('2000', '2000', '0', '0');

        if ($precalculated) {
            $emissionsBySource[Source::FuelAndEnergyRelated->value] = new Emissions('590', '590', '0', '0');
            $emissionsByScope[Scope::Scope3->value] = new Emissions('590', '590', '0', '0');
            $emissionsByScopeAndSource[Scope::Scope3->value][Source::FuelAndEnergyRelated->value] = new Emissions('590', '590', '0', '0');
            $activityLines = [
                new OrganizationalCarbonFootprintReportActivityLine(Scope::Scope1, Source::FixedFacilities, $isCompany ? 'Hotel Sol' : null, 'Caldera comunicada', ActivityType::ReportedEmissions, CalculationMethod::ReportedByOrganization, 180.0, Unit::Kilogram, '1', Unit::Kilogram, 2025, null, '180'),
                new OrganizationalCarbonFootprintReportActivityLine(Scope::Scope1, Source::OwnVehicles, null, 'Flota comunicada', ActivityType::ReportedEmissions, CalculationMethod::ReportedByOrganization, 1250.0, Unit::Kilogram, '1', Unit::Kilogram, 2025, null, '1250'),
                new OrganizationalCarbonFootprintReportActivityLine(Scope::Scope2, Source::BuildingElectricity, $isCompany ? 'Hotel Sol' : null, 'Electricidad comunicada', ActivityType::ReportedEmissions, CalculationMethod::ReportedByOrganization, 2500.0, Unit::Kilogram, '1', Unit::Kilogram, 2025, null, '2500'),
                new OrganizationalCarbonFootprintReportActivityLine(Scope::Scope3, Source::FuelAndEnergyRelated, $isCompany ? 'Hotel Sol' : null, 'Combustibles y energía comunicados', ActivityType::ReportedEmissions, CalculationMethod::ReportedByOrganization, 590.0, Unit::Kilogram, '1', Unit::Kilogram, 2025, null, '590'),
            ];
            $derivedLines = [];
            $totalEmissions = new Emissions('4520', '4520', '0', '0');
            $locationBasedElectricityEmissions = new Emissions();
            $locationBasedTotalEmissions = new Emissions('4520', '4520', '0', '0');
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
            locationBasedElectricityEmissions: $locationBasedElectricityEmissions,
            locationBasedTotalEmissions: $locationBasedTotalEmissions,
            electricityActivitiesWithoutLocationBased: $electricityActivitiesWithoutLocationBased,
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
            precalculated: $precalculated,
            gwpCh4: 27.9,
            gwpN2O: 273.0,
            operatingRegime: $isCompany ? null : OperatingRegime::Owned,
        );
    }

    private static function withMaterialityAnnex(OrganizationalCarbonFootprintReportData $data, OrganizationalCarbonFootprintReportMaterialityAnnex $annex): OrganizationalCarbonFootprintReportData
    {
        $exclusionLines = $data->exclusionLines;

        foreach ($annex->topics as $topic) {
            if ($topic->isExcluded() && null !== $topic->source && null !== $topic->exclusionReason) {
                $exclusionLines[] = new OrganizationalCarbonFootprintReportExclusionLine(Scope::Scope3, $topic->source, $topic->exclusionReason, $topic->exclusionComment);
            }
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
            locationBasedTotalEmissions: $data->locationBasedTotalEmissions,
            electricityActivitiesWithoutLocationBased: $data->electricityActivitiesWithoutLocationBased,
            emissionsBySource: $data->emissionsBySource,
            emissionsByScope: $data->emissionsByScope,
            emissionsByScopeAndSource: $data->emissionsByScopeAndSource,
            emissionsByProperty: $data->emissionsByProperty,
            activityLines: $data->activityLines,
            exclusionLines: $exclusionLines,
            mitigationPlan: $data->mitigationPlan,
            scope3Excluded: $data->scope3Excluded,
            scope3ExclusionReason: $data->scope3ExclusionReason,
            scope3ExclusionComment: $data->scope3ExclusionComment,
            derivedLines: $data->derivedLines,
            nonConsolidatedPropertyLines: $data->nonConsolidatedPropertyLines,
            precalculated: $data->precalculated,
            gwpCh4: $data->gwpCh4,
            gwpN2O: $data->gwpN2O,
            operatingRegime: $data->operatingRegime,
            boundaryLines: $data->boundaryLines,
            employeeSurvey: $data->employeeSurvey,
            materialityAnnex: $annex,
        );
    }
}
