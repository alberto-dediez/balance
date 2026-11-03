<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

final readonly class MaterialityAssessmentSnapshotAnswer
{
    public function __construct(
        public string $topicId,
        public string $questionId,
        public string $criterionKey,
        public string $text,
        public string $answerLabelKey,
        public string $answerScaleKey,
        public int $weight,
        public ?int $value,
        public int $points,
        public int $minPoints,
        public int $maxPoints,
        public \DateTimeImmutable $questionUpdatedAt,
        public bool $questionChangedSince,
        public bool $questionRemoved,
    ) {}
}
