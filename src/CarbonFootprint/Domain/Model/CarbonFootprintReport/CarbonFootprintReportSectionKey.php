<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

enum CarbonFootprintReportSectionKey: string
{
    case ExecutiveSummary           = 'executiveSummary';
    case Methodology                = 'methodology';
    case EmissionFactorSources      = 'emissionFactorSources';
    case AnalysisAndRecommendations = 'analysisAndRecommendations';
    case Compensation               = 'compensation';
    case Purpose                    = 'purpose';
    case RegulatoryContext          = 'regulatoryContext';
    case SustainableDevelopmentGoals = 'sustainableDevelopmentGoals';
    case Conclusion                 = 'conclusion';

    /** @return list<self> */
    public static function requiredFor(CarbonFootprintReportSubjectType $type): array
    {
        return match ($type) {
            CarbonFootprintReportSubjectType::Event => [self::ExecutiveSummary, self::Methodology, self::EmissionFactorSources, self::AnalysisAndRecommendations],
            CarbonFootprintReportSubjectType::Property, CarbonFootprintReportSubjectType::Company => [self::Purpose, self::RegulatoryContext, self::SustainableDevelopmentGoals, self::Conclusion],
        };
    }

    /** @return list<self> */
    public static function optionalFor(CarbonFootprintReportSubjectType $type): array
    {
        return match ($type) {
            CarbonFootprintReportSubjectType::Event => [self::Compensation],
            CarbonFootprintReportSubjectType::Property, CarbonFootprintReportSubjectType::Company => [],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ExecutiveSummary => 'Resumen ejecutivo',
            self::Methodology => 'Metodología de cálculo',
            self::EmissionFactorSources => 'Fuentes de los factores de emisión',
            self::AnalysisAndRecommendations => 'Análisis y recomendaciones',
            self::Compensation => 'Compensación de emisiones',
            self::Purpose => 'Propósito',
            self::RegulatoryContext => 'Contexto normativo',
            self::SustainableDevelopmentGoals => 'Relación con los ODS',
            self::Conclusion => 'Conclusión',
        };
    }
}
