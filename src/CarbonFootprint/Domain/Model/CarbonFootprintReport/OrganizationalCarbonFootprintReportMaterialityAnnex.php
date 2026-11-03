<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;

final readonly class OrganizationalCarbonFootprintReportMaterialityAnnex
{
    /**
     * @param list<MaterialityAnnexTopicLine>     $topics
     * @param list<MaterialityAnnexJustification> $justifications
     */
    public function __construct(
        public ?MaterialityAssessmentSnapshot $assessment,
        public bool $hasDecisions,
        public array $topics,
        public array $justifications,
    ) {}

    public function hasAssessment(): bool
    {
        return null !== $this->assessment;
    }

    public function showsAnswers(): bool
    {
        return true === $this->assessment?->completed;
    }

    public function isReopened(): bool
    {
        return null !== $this->assessment && !$this->assessment->completed;
    }

    public function isAnalysisMissing(): bool
    {
        if ($this->hasDecisions) {
            return false;
        }

        if (!$this->showsAnswers()) {
            return true;
        }

        foreach ($this->assessment->topics ?? [] as $topic) {
            if (null !== $topic->verdict) {
                return false;
            }
        }

        return true;
    }

    public function topicFor(Source $source): ?MaterialityAnnexTopicLine
    {
        foreach ($this->topics as $topic) {
            if ($topic->source === $source) {
                return $topic;
            }
        }

        return null;
    }

    public function isEmpty(): bool
    {
        return !$this->hasAssessment() && !$this->hasDecisions;
    }
}
