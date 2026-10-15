<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ProcessAnswer;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\ProcessAnswerHttpMapper;
use PHPUnit\Framework\TestCase;

class ProcessAnswerHttpMapperTest extends TestCase
{
    private ProcessAnswerHttpMapper $sut;

    protected function setUp(): void
    {
        $this->sut = new ProcessAnswerHttpMapper();
    }

    public function testActivityTypeIsProcess(): void
    {
        $this->assertSame(ActivityType::Process, $this->sut->activityType());
    }

    public function testMapsAValidRowToATypedAnswer(): void
    {
        $answer = $this->sut->map(['itemId' => 'profile-1', 'unit' => 'ud', 'quantity' => '1500']);

        $this->assertInstanceOf(ProcessAnswer::class, $answer);
        $this->assertSame('profile-1', (string) $answer->processId);
        $this->assertSame(Unit::Piece, $answer->quantity->unit());
        $this->assertSame(1500.0, $answer->quantity->value());
        $this->assertNull($answer->existingActivityId);
    }

    public function testMapsTheExistingActivityId(): void
    {
        $answer = $this->sut->map(['itemId' => 'profile-1', 'unit' => 'kWh', 'quantity' => '10', 'activityId' => 'activity-1']);

        $this->assertInstanceOf(ProcessAnswer::class, $answer);
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
