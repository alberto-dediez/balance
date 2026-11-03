<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

final readonly class MaterialityAssessmentSnapshot
{
    /**
     * @param list<MaterialityAssessmentSnapshotTopic>  $topics
     * @param list<MaterialityAssessmentSnapshotAnswer> $answers
     */
    public function __construct(
        public string $assessmentId,
        public bool $completed,
        public \DateTimeImmutable $startedAt,
        public ?\DateTimeImmutable $completedAt,
        public ?string $completedByName,
        public ?string $sectorName,
        public ?int $thresholdPercent,
        public ?bool $highMagnitudeRule,
        public ?\DateTimeImmutable $questionSetVersion,
        public bool $questionSetChangedSince,
        public array $topics,
        public array $answers,
    ) {}

    /** @return list<MaterialityAssessmentSnapshotAnswer> */
    public function answersOf(string $topicId): array
    {
        return array_values(array_filter(
            $this->answers,
            static fn(MaterialityAssessmentSnapshotAnswer $answer): bool => $answer->topicId === $topicId,
        ));
    }
}
