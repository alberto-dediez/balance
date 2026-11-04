<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSectionKey;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use PHPUnit\Framework\TestCase;

class CarbonFootprintReportSectionKeyTest extends TestCase
{
    public function testEventSections(): void
    {
        $this->assertSame(
            [CarbonFootprintReportSectionKey::ExecutiveSummary, CarbonFootprintReportSectionKey::Methodology, CarbonFootprintReportSectionKey::EmissionFactorSources, CarbonFootprintReportSectionKey::AnalysisAndRecommendations],
            CarbonFootprintReportSectionKey::requiredFor(CarbonFootprintReportSubjectType::Event),
        );
        $this->assertSame([CarbonFootprintReportSectionKey::Compensation], CarbonFootprintReportSectionKey::optionalFor(CarbonFootprintReportSubjectType::Event));
    }

    public function testOrganizationalSections(): void
    {
        foreach ([CarbonFootprintReportSubjectType::Property, CarbonFootprintReportSubjectType::Company] as $type) {
            $this->assertSame(
                [
                    CarbonFootprintReportSectionKey::Purpose,
                    CarbonFootprintReportSectionKey::RegulatoryContext,
                    CarbonFootprintReportSectionKey::Methodology,
                    CarbonFootprintReportSectionKey::EmissionFactorSources,
                    CarbonFootprintReportSectionKey::Uncertainty,
                    CarbonFootprintReportSectionKey::SustainableDevelopmentGoals,
                    CarbonFootprintReportSectionKey::Conclusion,
                ],
                CarbonFootprintReportSectionKey::requiredFor($type),
            );
            $this->assertSame([CarbonFootprintReportSectionKey::MitigationPlan, CarbonFootprintReportSectionKey::MaterialityAnalysis], CarbonFootprintReportSectionKey::optionalFor($type));
        }
    }

    public function testEveryKeyHasALabel(): void
    {
        foreach (CarbonFootprintReportSectionKey::cases() as $key) {
            $this->assertNotSame('', $key->label());
        }

        $this->assertSame('Análisis de materialidad del alcance 3', CarbonFootprintReportSectionKey::MaterialityAnalysis->label());
    }

    public function testUncertaintyHasALabelAndIsRequired(): void
    {
        $this->assertSame('Incertidumbre y calidad de los datos', CarbonFootprintReportSectionKey::Uncertainty->label());
        $this->assertFalse(CarbonFootprintReportSectionKey::Uncertainty->isOptional());
    }

    public function testOnlyCompensationMitigationPlanAndMaterialityAnalysisAreOptional(): void
    {
        foreach (CarbonFootprintReportSectionKey::cases() as $key) {
            $this->assertSame(
                in_array($key, [CarbonFootprintReportSectionKey::Compensation, CarbonFootprintReportSectionKey::MitigationPlan, CarbonFootprintReportSectionKey::MaterialityAnalysis], true),
                $key->isOptional(),
                $key->value,
            );
        }
    }
}
