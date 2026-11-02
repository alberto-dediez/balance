<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\NonConsolidatedPropertyLine;
use App\CarbonFootprint\Domain\Model\Magnitude;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\Unit;

enum CalculationMethod: string
{
    case SupplierSpecific            = 'supplier_specific';
    case ReportedByOrganization      = 'reported_by_organization';
    case ActivityBased               = 'activity_based';
    case SpendBased                  = 'spend_based';
    case DistanceBased               = 'distance_based';
    case FuelBased                   = 'fuel_based';
    case WasteTypeSpecific           = 'waste_type_specific';
    case AverageData                 = 'average_data';
    case EmployeeSurveyExtrapolation = 'employee_survey_extrapolation';
    case DerivedWellToTank           = 'derived_well_to_tank';
    case OperatorFootprint           = 'operator_footprint';
    case OperatorContribution        = 'operator_contribution';
    case ManualValue                 = 'manual_value';

    public static function forActivity(ActivityType $activityType, Unit $unit, ActivityPlacement $placement): self
    {
        $isTransportOrMobility = in_array($activityType, [ActivityType::Transport, ActivityType::Mobility], true);

        return match (true) {
            ActivityType::ReportedEmissions === $activityType && null !== $placement->providerId => self::SupplierSpecific,
            ActivityType::ReportedEmissions === $activityType => self::ReportedByOrganization,
            $unit->isCurrency() => self::SpendBased,
            $placement->fromEmployees => self::EmployeeSurveyExtrapolation,
            Scope::Scope3 !== $placement->stage => self::ActivityBased,
            $isTransportOrMobility && in_array($unit->magnitude(), [Magnitude::Distance, Magnitude::Freight], true) => self::DistanceBased,
            $isTransportOrMobility && in_array($unit->magnitude(), [Magnitude::Volume, Magnitude::Energy, Magnitude::Mass], true) => self::FuelBased,
            ActivityType::Waste === $activityType => self::WasteTypeSpecific,
            default => self::AverageData,
        };
    }

    public static function forNonConsolidatedPropertyLine(NonConsolidatedPropertyLine $line): ?self
    {
        return match (true) {
            null !== $line->manualCo2eq => self::ManualValue,
            null !== $line->operatorContributionId => self::OperatorContribution,
            null !== $line->propertyCarbonFootprintId => self::OperatorFootprint,
            default => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::SupplierSpecific => 'Específico del proveedor',
            self::ReportedByOrganization => 'Comunicado por la organización',
            self::ActivityBased => 'Basado en datos de actividad',
            self::SpendBased => 'Basado en gasto',
            self::DistanceBased => 'Basado en distancia',
            self::FuelBased => 'Basado en combustible',
            self::WasteTypeSpecific => 'Específico por tipo de residuo',
            self::AverageData => 'Basado en datos medios',
            self::EmployeeSurveyExtrapolation => 'Extrapolación de la encuesta de empleados',
            self::DerivedWellToTank => 'Derivado: emisiones del pozo al tanque (WTT)',
            self::OperatorFootprint => 'Huella de la propiedad',
            self::OperatorContribution => 'Aportación del franquiciado o arrendatario',
            self::ManualValue => 'Valor manual',
        };
    }

    public function dataQuality(): DataQuality
    {
        return match ($this) {
            self::SupplierSpecific, self::OperatorFootprint => DataQuality::High,
            self::ReportedByOrganization, self::ActivityBased, self::DistanceBased, self::FuelBased, self::WasteTypeSpecific, self::AverageData, self::EmployeeSurveyExtrapolation, self::DerivedWellToTank, self::OperatorContribution => DataQuality::Medium,
            self::SpendBased, self::ManualValue => DataQuality::Low,
        };
    }
}
