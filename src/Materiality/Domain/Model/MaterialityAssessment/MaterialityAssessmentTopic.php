<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityAssessment;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityVerdict;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicDirection;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;

class MaterialityAssessmentTopic
{
    public function __construct(
        private MaterialityAssessmentTopicId $id,
        private MaterialityAssessment $assessment,
        private MaterialityTopicId $topicId,
        private ?int $topicNumber,
        private MaterialityScope $scope,
        private ?MaterialityTopicDirection $direction,
        private string $name,
        private string $description,
        private int $position,
        private ?int $points = null,
        private ?int $minPoints = null,
        private ?int $maxPoints = null,
        private ?int $highestMagnitudeLevel = null,
        private ?int $thresholdPercent = null,
        private ?bool $forcedByHighMagnitude = null,
        private ?MaterialityVerdict $verdict = null,
    ) {}

    public function id(): MaterialityAssessmentTopicId
    {
        return $this->id;
    }

    public function assessment(): MaterialityAssessment
    {
        return $this->assessment;
    }

    public function topicId(): MaterialityTopicId
    {
        return $this->topicId;
    }

    public function topicNumber(): ?int
    {
        return $this->topicNumber;
    }

    public function scope(): MaterialityScope
    {
        return $this->scope;
    }

    public function direction(): ?MaterialityTopicDirection
    {
        return $this->direction;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function points(): ?int
    {
        return $this->points;
    }

    public function minPoints(): ?int
    {
        return $this->minPoints;
    }

    public function maxPoints(): ?int
    {
        return $this->maxPoints;
    }

    public function highestMagnitudeLevel(): ?int
    {
        return $this->highestMagnitudeLevel;
    }

    public function thresholdPercent(): ?int
    {
        return $this->thresholdPercent;
    }

    public function isForcedByHighMagnitude(): bool
    {
        return true === $this->forcedByHighMagnitude;
    }

    public function verdict(): ?MaterialityVerdict
    {
        return $this->verdict;
    }

    public function hasVerdict(): bool
    {
        return null !== $this->verdict;
    }

    public function scorePercent(): ?float
    {
        if (null === $this->points || null === $this->minPoints || null === $this->maxPoints) {
            return null;
        }

        return self::scorePercentOf($this->points, $this->minPoints, $this->maxPoints);
    }

    public static function scorePercentOf(int $points, int $minPoints, int $maxPoints): ?float
    {
        if ($maxPoints <= $minPoints) {
            return null;
        }

        return round(100 * ($points - $minPoints) / ($maxPoints - $minPoints), 2);
    }

    public function score(
        int $points,
        int $minPoints,
        int $maxPoints,
        ?int $highestMagnitudeLevel,
        ?int $thresholdPercent = null,
        ?bool $forcedByHighMagnitude = null,
        ?MaterialityVerdict $verdict = null,
    ): void {
        $this->points = $points;
        $this->minPoints = $minPoints;
        $this->maxPoints = $maxPoints;
        $this->highestMagnitudeLevel = $highestMagnitudeLevel;
        $this->thresholdPercent = $thresholdPercent;
        $this->forcedByHighMagnitude = $forcedByHighMagnitude;
        $this->verdict = $verdict;
    }

    public function clearScore(): void
    {
        $this->points = null;
        $this->minPoints = null;
        $this->maxPoints = null;
        $this->highestMagnitudeLevel = null;
        $this->thresholdPercent = null;
        $this->forcedByHighMagnitude = null;
        $this->verdict = null;
    }
}
