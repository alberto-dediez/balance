<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

final readonly class MaterialityAnnexJustification
{
    /** @param list<MaterialityAssessmentSnapshotAnswer> $answers */
    public function __construct(
        public MaterialityAnnexTopicLine $topic,
        public array $answers,
        public ?int $points,
        public ?int $minPoints,
        public ?int $maxPoints,
    ) {}
}
