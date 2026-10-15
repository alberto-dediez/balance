<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\UseProfileAnswer;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\UseProfileAnswerHttpMapper;
use PHPUnit\Framework\TestCase;

class UseProfileAnswerHttpMapperTest extends TestCase
{
    private UseProfileAnswerHttpMapper $sut;

    protected function setUp(): void
    {
        $this->sut = new UseProfileAnswerHttpMapper();
    }

    public function testActivityTypeIsUseProfile(): void
    {
        $this->assertSame(ActivityType::UseProfile, $this->sut->activityType());
    }

    public function testMapsAValidRowToATypedAnswer(): void
    {
        $answer = $this->sut->map(['itemId' => 'profile-1', 'unit' => 'ud', 'quantity' => '1500']);

        $this->assertInstanceOf(UseProfileAnswer::class, $answer);
        $this->assertSame('profile-1', (string) $answer->useProfileId);
        $this->assertSame(Unit::Piece, $answer->quantity->unit());
        $this->assertSame(1500.0, $answer->quantity->value());
        $this->assertNull($answer->existingActivityId);
    }

    public function testMapsTheExistingActivityId(): void
    {
        $answer = $this->sut->map(['itemId' => 'profile-1', 'unit' => 'kWh', 'quantity' => '10', 'activityId' => 'activity-1']);

        $this->assertInstanceOf(UseProfileAnswer::class, $answer);
        $this->assertSame(Unit::KWh, $answer->quantity->unit());
        $this->assertNotNull($answer->existingActivityId);
        $this->assertSame('activity-1', (string) $answer->existingActivityId);
    }

    public function testReturnsNullWhenItemIdIsEmpty(): void
    {
        $this->assertNull($this->sut->map(['itemId' => '', 'unit' => 'ud', 'quantity' => '10']));
    }

    public function testReturnsNullWhenQuantityIsNegative(): void
    {
        $this->assertNull($this->sut->map(['itemId' => 'profile-1', 'unit' => 'ud', 'quantity' => '-5']));
    }

    public function testReturnsNullWhenUnitIsInvalid(): void
    {
        $this->assertNull($this->sut->map(['itemId' => 'profile-1', 'unit' => 'invalid', 'quantity' => '10']));
    }
}
