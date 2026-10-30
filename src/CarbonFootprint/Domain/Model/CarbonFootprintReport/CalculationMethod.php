<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\NonConsolidatedPropertyLine;

enum CalculationMethod: string
{
    case SupplierSpecific            = 'supplier_specific';
    case ReportedByOrganization      = 'reported_by_organization';
    case ActivityBased               = 'activity_based';
    case SpendBased                  = 'spend_based';
    case EmployeeSurveyExtrapolation = 'employee_survey_extrapolation';
    case DerivedWellToTank           = 'derived_well_to_tank';
    case OperatorFootprint           = 'operator_footprint';
    case OperatorContribution        = 'operator_contribution';
    case ManualValue                 = 'manual_value';

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
            self::ReportedByOrganization, self::ActivityBased, self::EmployeeSurveyExtrapolation, self::DerivedWellToTank, self::OperatorContribution => DataQuality::Medium,
            self::SpendBased, self::ManualValue => DataQuality::Low,
        };
    }
}
