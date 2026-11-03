<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprint\Materiality;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecision;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SourceExclusion;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;

class MaterialitySummaryBuilder
{
    /**
     * @param list<MaterialitySummaryTopicInput> $topics
     *
     * @return list<MaterialitySummaryRow>
     */
    public function build(CarbonFootprint $carbonFootprint, CarbonFootprintReportSubjectType $subjectType, array $topics): array
    {
        $stageExcluded = $carbonFootprint->isStageExcluded(Scope::Scope3);
        $exclusions = $carbonFootprint->exclusionsFor(Scope::Scope3);
        $decisionsByTopic = [];

        foreach ($carbonFootprint->materialityDecisions() as $decision) {
            $decisionsByTopic[$decision->topicId()] = $decision;
        }

        $rows = [];
        $evaluatedSources = [];

        foreach ($topics as $topic) {
            $decision = $decisionsByTopic[$topic->topicId] ?? null;
            $source = null === $topic->topicNumber ? null : Source::ofGhgProtocolCategory($topic->topicNumber);
            $exclusion = null !== $source ? ($exclusions[$source->value] ?? null) : null;
            $scorePercent = $topic->scorePercent ?? $decision?->scorePercent();

            if (null !== $source) {
                $evaluatedSources[$source->value] = true;
            }

            $rows[] = new MaterialitySummaryRow(
                topicId: $topic->topicId,
                topicNumber: $topic->topicNumber,
                scorePercent: $scorePercent,
                thresholdPercent: $topic->thresholdPercent ?? $decision?->thresholdPercent(),
                forcedByHighMagnitude: null !== $topic->verdict ? $topic->forcedByHighMagnitude : (true === $decision?->isForcedByHighMagnitude()),
                verdict: $topic->verdict ?? $decision?->verdict(),
                source: $source,
                state: $this->stateOf($subjectType, $stageExcluded, $source, $exclusion, $decision, null !== ($topic->verdict ?? $decision?->verdict()), $scorePercent),
                exclusion: $exclusion,
                decision: $decision,
            );
        }

        foreach (Scope::Scope3->sourcesFor($subjectType) as $source) {
            if (isset($evaluatedSources[$source->value])) {
                continue;
            }

            $rows[] = new MaterialitySummaryRow(
                topicId: null,
                topicNumber: $source->ghgProtocolCategory(),
                scorePercent: null,
                thresholdPercent: null,
                forcedByHighMagnitude: false,
                verdict: null,
                source: $source,
                state: $stageExcluded ? MaterialityCategoryState::StageExcluded : MaterialityCategoryState::NotEvaluated,
                exclusion: $exclusions[$source->value] ?? null,
                decision: null,
            );
        }

        return $rows;
    }

    private function stateOf(
        CarbonFootprintReportSubjectType $subjectType,
        bool $stageExcluded,
        ?Source $source,
        ?SourceExclusion $exclusion,
        ?MaterialityDecision $decision,
        bool $hasVerdict,
        ?float $scorePercent,
    ): MaterialityCategoryState {
        if (!$hasVerdict) {
            return null === $scorePercent ? MaterialityCategoryState::WithoutScore : MaterialityCategoryState::NotEvaluated;
        }

        if (null === $source) {
            return MaterialityCategoryState::WithoutSource;
        }

        if (!in_array($source, Scope::Scope3->sourcesFor($subjectType), true)) {
            return MaterialityCategoryState::NotApplicableToSubject;
        }

        if ($stageExcluded) {
            return MaterialityCategoryState::StageExcluded;
        }

        if (null === $decision) {
            return null !== $exclusion ? MaterialityCategoryState::ExcludedRecommended : MaterialityCategoryState::Active;
        }

        $excluded = null !== $exclusion;

        if ($decision->recommendsExclusion()) {
            if ($excluded) {
                return MaterialityCategoryState::ExcludedRecommended;
            }

            return $decision->isOverridden() ? MaterialityCategoryState::ActiveOverridden : MaterialityCategoryState::PendingReview;
        }

        if (!$excluded) {
            return MaterialityCategoryState::Active;
        }

        return $decision->isOverridden() ? MaterialityCategoryState::ExcludedOverridden : MaterialityCategoryState::PendingReview;
    }
}
