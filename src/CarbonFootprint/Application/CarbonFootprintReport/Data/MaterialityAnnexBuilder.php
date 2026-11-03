<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Data;

use App\CarbonFootprint\Application\CarbonFootprint\Materiality\MaterialitySummaryBuilder;
use App\CarbonFootprint\Application\CarbonFootprint\Materiality\MaterialitySummaryRow;
use App\CarbonFootprint\Application\CarbonFootprint\Materiality\MaterialitySummaryTopicInput;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\CarbonFootprint;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecision;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAnnexJustification;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAnnexTopicLine;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAssessmentSnapshot;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAssessmentSnapshotAnswer;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAssessmentSnapshotProvider;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\MaterialityAssessmentSnapshotTopic;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportMaterialityAnnex;
use App\Security\Domain\Model\User\UserRepository;

final class MaterialityAnnexBuilder
{
    public function __construct(
        private MaterialityAssessmentSnapshotProvider $materialityAssessmentSnapshotProvider,
        private MaterialitySummaryBuilder $materialitySummaryBuilder,
        private UserRepository $userRepository,
    ) {}

    public function build(CarbonFootprint $carbonFootprint, CarbonFootprintReportSubjectType $subjectType): OrganizationalCarbonFootprintReportMaterialityAnnex
    {
        $snapshot = $this->materialityAssessmentSnapshotProvider->find($subjectType, (string) $carbonFootprint->id());
        $decisions = $carbonFootprint->materialityDecisions();

        $snapshotTopics = [];

        foreach ($snapshot->topics ?? [] as $topic) {
            $snapshotTopics[$topic->topicId] = $topic;
        }

        $inputs = null !== $snapshot
            ? array_map(static fn(MaterialityAssessmentSnapshotTopic $topic): MaterialitySummaryTopicInput => new MaterialitySummaryTopicInput(
                topicId: $topic->topicId,
                topicNumber: $topic->topicNumber,
                scorePercent: $topic->scorePercent,
                thresholdPercent: $topic->thresholdPercent,
                forcedByHighMagnitude: $topic->forcedByHighMagnitude,
                verdict: $topic->verdict,
            ), $snapshot->topics)
            : array_map(static fn(MaterialityDecision $decision): MaterialitySummaryTopicInput => new MaterialitySummaryTopicInput(
                topicId: $decision->topicId(),
                topicNumber: $decision->topicNumber(),
                scorePercent: null,
                thresholdPercent: null,
                forcedByHighMagnitude: false,
                verdict: null,
            ), $decisions);

        $showsAnswers = true === $snapshot?->completed;
        $topics = [];
        $justifications = [];

        foreach ($this->materialitySummaryBuilder->build($carbonFootprint, $subjectType, $inputs) as $row) {
            $snapshotTopic = null !== $row->topicId ? ($snapshotTopics[$row->topicId] ?? null) : null;
            $line = $this->lineOf($row, $snapshotTopic);
            $topics[] = $line;

            if (!$line->isExcluded() || null === $line->source) {
                continue;
            }

            $justifications[] = new MaterialityAnnexJustification(
                topic: $line,
                answers: $showsAnswers && null !== $line->topicId ? $this->answersOf($snapshot, $line->topicId) : [],
                points: $snapshotTopic?->points,
                minPoints: $snapshotTopic?->minPoints,
                maxPoints: $snapshotTopic?->maxPoints,
            );
        }

        return new OrganizationalCarbonFootprintReportMaterialityAnnex(
            assessment: $snapshot,
            hasDecisions: [] !== $decisions,
            topics: $topics,
            justifications: $justifications,
        );
    }

    private function lineOf(MaterialitySummaryRow $row, ?MaterialityAssessmentSnapshotTopic $snapshotTopic): MaterialityAnnexTopicLine
    {
        $decision = $row->decision;
        $overridden = true === $decision?->isOverridden();

        return new MaterialityAnnexTopicLine(
            topicId: $row->topicId,
            number: $row->topicNumber,
            name: $snapshotTopic?->name,
            source: $row->source,
            scorePercent: $row->scorePercent,
            thresholdPercent: $row->thresholdPercent,
            forcedByHighMagnitude: $row->forcedByHighMagnitude,
            verdict: $row->verdict,
            state: $row->state,
            exclusionReason: $row->exclusion?->reason(),
            exclusionComment: $row->exclusion?->comment(),
            overriddenByName: $overridden ? $this->userName($decision) : null,
            overriddenAt: $overridden ? $decision->overriddenAt() : null,
            overrideJustification: $overridden ? $decision->overrideJustification() : null,
            recommendedAt: $decision?->recommendedAt(),
        );
    }

    private function userName(MaterialityDecision $decision): string
    {
        $userId = $decision->overriddenByUserId();
        $user = null === $userId ? null : $this->userRepository->find($userId);

        return null === $user ? '---' : (string) $user;
    }

    /** @return list<MaterialityAssessmentSnapshotAnswer> */
    private function answersOf(?MaterialityAssessmentSnapshot $snapshot, string $topicId): array
    {
        return null === $snapshot ? [] : $snapshot->answersOf($topicId);
    }
}
