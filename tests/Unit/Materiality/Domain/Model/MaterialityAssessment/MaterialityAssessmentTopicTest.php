<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialityAssessment;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityVerdict;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentTopic;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentTopicId;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityScope;
use App\Materiality\Domain\Model\MaterialityTopic\MaterialityTopicId;
use PHPUnit\Framework\TestCase;

final class MaterialityAssessmentTopicTest extends TestCase
{
    public function testScorePercentIsNormalizedWithTwoDecimals(): void
    {
        $this->assertSame(16.67, $this->scored(8, 6, 18)->scorePercent());
        $this->assertSame(75.0, $this->scored(15, 6, 18)->scorePercent());
    }

    public function testScorePercentAtTheMinimumIsZero(): void
    {
        $this->assertSame(0.0, $this->scored(6, 6, 18)->scorePercent());
    }

    public function testScorePercentAtTheMaximumIsOneHundred(): void
    {
        $this->assertSame(100.0, $this->scored(18, 6, 18)->scorePercent());
    }

    public function testScorePercentIsNullWithoutRange(): void
    {
        $this->assertNull($this->scored(0, 0, 0)->scorePercent());
    }

    public function testScorePercentIsNullWhenNotScored(): void
    {
        $this->assertNull($this->topic()->scorePercent());
    }

    public function testClearScore(): void
    {
        $topic = $this->scored(15, 6, 18);

        $topic->clearScore();

        $this->assertNull($topic->points());
        $this->assertNull($topic->scorePercent());
    }

    public function testScoreWithVerdict(): void
    {
        $topic = $this->topic();

        $topic->score(15, 6, 18, 3, 50, true, MaterialityVerdict::Calculate);

        $this->assertTrue($topic->hasVerdict());
        $this->assertSame(MaterialityVerdict::Calculate, $topic->verdict());
        $this->assertSame(50, $topic->thresholdPercent());
        $this->assertTrue($topic->isForcedByHighMagnitude());
    }

    public function testScoreWithoutVerdict(): void
    {
        $this->assertFalse($this->scored(15, 6, 18)->hasVerdict());
    }

    public function testClearScoreClearsTheVerdict(): void
    {
        $topic = $this->topic();
        $topic->score(15, 6, 18, 3, 50, true, MaterialityVerdict::Calculate);

        $topic->clearScore();

        $this->assertFalse($topic->hasVerdict());
        $this->assertNull($topic->thresholdPercent());
        $this->assertFalse($topic->isForcedByHighMagnitude());
    }

    public function testScorePercentOf(): void
    {
        $this->assertSame(75.0, MaterialityAssessmentTopic::scorePercentOf(15, 6, 18));
        $this->assertNull(MaterialityAssessmentTopic::scorePercentOf(0, 0, 0));
    }

    private function scored(int $points, int $minPoints, int $maxPoints): MaterialityAssessmentTopic
    {
        $topic = $this->topic();
        $topic->score($points, $minPoints, $maxPoints, null);

        return $topic;
    }

    private function topic(): MaterialityAssessmentTopic
    {
        return new MaterialityAssessmentTopic(
            id: MaterialityAssessmentTopicId::generate(),
            assessment: MaterialityAssessmentMother::draft(),
            topicId: MaterialityTopicId::generate(),
            topicNumber: 1,
            scope: MaterialityScope::Scope3,
            direction: null,
            name: 'Topic',
            description: '',
            position: 1,
        );
    }
}
