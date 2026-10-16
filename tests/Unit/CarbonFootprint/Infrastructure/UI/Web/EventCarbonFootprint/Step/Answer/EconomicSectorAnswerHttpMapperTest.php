<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\EconomicSectorAnswer;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\EconomicSectorAnswerHttpMapper;
use PHPUnit\Framework\TestCase;

class EconomicSectorAnswerHttpMapperTest extends TestCase
{
    private EconomicSectorAnswerHttpMapper $sut;

    protected function setUp(): void
    {
        $this->sut = new EconomicSectorAnswerHttpMapper();
    }

    public function testActivityTypeIsEconomicSector(): void
    {
        $this->assertSame(ActivityType::EconomicSector, $this->sut->activityType());
    }

    public function testMapsAValidRowToATypedAnswer(): void
    {
        $answer = $this->sut->map(['itemId' => 'sector-1', 'unit' => 'EUR', 'quantity' => '1500']);

        $this->assertInstanceOf(EconomicSectorAnswer::class, $answer);
        $this->assertSame('sector-1', (string) $answer->economicSectorId);
        $this->assertSame(Unit::Euro, $answer->quantity->unit());
        $this->assertSame(1500.0, $answer->quantity->value());
        $this->assertNull($answer->existingActivityId);
    }

    public function testMapsTheExistingActivityId(): void
    {
        $answer = $this->sut->map(['itemId' => 'sector-1', 'unit' => 'USD', 'quantity' => '10', 'activityId' => 'activity-1']);

        $this->assertInstanceOf(EconomicSectorAnswer::class, $answer);
        $this->assertSame(Unit::UsDollar, $answer->quantity->unit());
        $this->assertNotNull($answer->existingActivityId);
        $this->assertSame('activity-1', (string) $answer->existingActivityId);
    }

    public function testReturnsNullWhenItemIdIsEmpty(): void
    {
        $this->assertNull($this->sut->map(['itemId' => '', 'unit' => 'EUR', 'quantity' => '10']));
    }

    public function testReturnsNullWhenQuantityIsNegative(): void
    {
        $this->assertNull($this->sut->map(['itemId' => 'sector-1', 'unit' => 'EUR', 'quantity' => '-5']));
    }

    public function testReturnsNullWhenUnitIsInvalid(): void
    {
        $this->assertNull($this->sut->map(['itemId' => 'sector-1', 'unit' => 'invalid', 'quantity' => '10']));
    }
}
