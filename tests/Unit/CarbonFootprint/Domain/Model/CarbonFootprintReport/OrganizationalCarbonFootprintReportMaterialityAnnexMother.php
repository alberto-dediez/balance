<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityCategoryState;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAnnexJustification;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAnnexTopicLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAssessmentSnapshot;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAssessmentSnapshotTopic;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportMaterialityAnnex;

class OrganizationalCarbonFootprintReportMaterialityAnnexMother
{
    public const string OVERRIDE_JUSTIFICATION = 'Exigencia del cliente principal';

    public static function complete(): OrganizationalCarbonFootprintReportMaterialityAnnex
    {
        return self::build(MaterialityAssessmentSnapshotMother::completed(), false, false);
    }

    public static function withOverride(): OrganizationalCarbonFootprintReportMaterialityAnnex
    {
        return self::build(MaterialityAssessmentSnapshotMother::completed(), true, false);
    }

    public static function withChangedQuestions(): OrganizationalCarbonFootprintReportMaterialityAnnex
    {
        return self::build(MaterialityAssessmentSnapshotMother::withChangedQuestions(), false, false);
    }

    public static function withoutAssessment(): OrganizationalCarbonFootprintReportMaterialityAnnex
    {
        return new OrganizationalCarbonFootprintReportMaterialityAnnex(null, false, [], []);
    }

    public static function decisionsWithoutAssessment(): OrganizationalCarbonFootprintReportMaterialityAnnex
    {
        return new OrganizationalCarbonFootprintReportMaterialityAnnex(null, true, [self::line(MaterialityCategoryState::ExcludedRecommended, name: null)], []);
    }

    public static function reopened(): OrganizationalCarbonFootprintReportMaterialityAnnex
    {
        return self::build(MaterialityAssessmentSnapshotMother::draft(), false, false);
    }

    public static function stageExcluded(): OrganizationalCarbonFootprintReportMaterialityAnnex
    {
        return self::build(MaterialityAssessmentSnapshotMother::completed(), false, true);
    }

    public static function completedWithoutVerdicts(): OrganizationalCarbonFootprintReportMaterialityAnnex
    {
        return new OrganizationalCarbonFootprintReportMaterialityAnnex(MaterialityAssessmentSnapshotMother::completedWithoutVerdicts(), false, [], []);
    }

    public static function completedWithoutScope3Topics(): OrganizationalCarbonFootprintReportMaterialityAnnex
    {
        return new OrganizationalCarbonFootprintReportMaterialityAnnex(MaterialityAssessmentSnapshotMother::completedWithoutScope3Topics(), false, [], []);
    }

    public static function withoutHighMagnitudeRule(): OrganizationalCarbonFootprintReportMaterialityAnnex
    {
        return self::build(MaterialityAssessmentSnapshotMother::withoutHighMagnitudeRule(), false, false);
    }

    public static function line(
        MaterialityCategoryState $state,
        ?Source $source = Source::SoldProductsProcessing,
        ?float $scorePercent = 0.0,
        ?string $overriddenByName = null,
        ?string $name = 'Procesamiento de los productos vendidos',
    ): MaterialityAnnexTopicLine {
        return new MaterialityAnnexTopicLine(
            topicId: MaterialityAssessmentSnapshotMother::topicId($source?->ghgProtocolCategory()),
            number: $source?->ghgProtocolCategory(),
            name: $name,
            source: $source,
            scorePercent: $scorePercent,
            thresholdPercent: null === $scorePercent ? null : 50,
            forcedByHighMagnitude: false,
            verdict: null,
            state: $state,
            exclusionReason: null,
            exclusionComment: null,
            overriddenByName: $overriddenByName,
            overriddenAt: null === $overriddenByName ? null : new \DateTimeImmutable('2026-03-14 11:00:00'),
            overrideJustification: null === $overriddenByName ? null : self::OVERRIDE_JUSTIFICATION,
            recommendedAt: null,
        );
    }

    private static function build(MaterialityAssessmentSnapshot $assessment, bool $withOverride, bool $stageExcluded): OrganizationalCarbonFootprintReportMaterialityAnnex
    {
        $completed = MaterialityAssessmentSnapshotMother::completed();
        $topics = [];
        $justifications = [];

        foreach ($completed->topics as $topic) {
            $source = null === $topic->topicNumber ? null : Source::ofGhgProtocolCategory($topic->topicNumber);
            $overridden = $withOverride && 11 === $topic->topicNumber;
            $excluded = !$stageExcluded && !$overridden && true === $topic->verdict?->recommendsExclusion();
            $state = match (true) {
                $stageExcluded => MaterialityCategoryState::StageExcluded,
                $overridden => MaterialityCategoryState::ActiveOverridden,
                $excluded => MaterialityCategoryState::ExcludedRecommended,
                default => MaterialityCategoryState::Active,
            };

            $line = new MaterialityAnnexTopicLine(
                topicId: $topic->topicId,
                number: $topic->topicNumber,
                name: $topic->name,
                source: $source,
                scorePercent: $topic->scorePercent,
                thresholdPercent: $topic->thresholdPercent,
                forcedByHighMagnitude: $topic->forcedByHighMagnitude,
                verdict: $topic->verdict,
                state: $state,
                exclusionReason: $excluded ? ExclusionReason::NotSignificant : null,
                exclusionComment: $excluded ? sprintf('Análisis de materialidad: relevancia %s %% por debajo del umbral 50 %%', number_format((float) $topic->scorePercent, 2, ',', '.')) : null,
                overriddenByName: $overridden ? 'A. García' : null,
                overriddenAt: $overridden ? new \DateTimeImmutable('2026-03-14 11:00:00') : null,
                overrideJustification: $overridden ? self::OVERRIDE_JUSTIFICATION : null,
                recommendedAt: new \DateTimeImmutable('2026-03-12 12:00:00'),
            );
            $topics[] = $line;

            if ($line->isExcluded() && null !== $source) {
                $justifications[] = self::justification($line, $assessment, $topic);
            }
        }

        return new OrganizationalCarbonFootprintReportMaterialityAnnex($assessment, true, $topics, $justifications);
    }

    private static function justification(MaterialityAnnexTopicLine $line, MaterialityAssessmentSnapshot $assessment, MaterialityAssessmentSnapshotTopic $topic): MaterialityAnnexJustification
    {
        return new MaterialityAnnexJustification(
            topic: $line,
            answers: $assessment->completed ? $assessment->answersOf($topic->topicId) : [],
            points: $assessment->completed ? $topic->points : null,
            minPoints: $assessment->completed ? $topic->minPoints : null,
            maxPoints: $assessment->completed ? $topic->maxPoints : null,
        );
    }
}
