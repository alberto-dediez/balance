<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\CapitalGoodAnswer;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\CapitalGoodAnswerHttpMapper;
use PHPUnit\Framework\TestCase;

class CapitalGoodAnswerHttpMapperTest extends TestCase
{
    private CapitalGoodAnswerHttpMapper $sut;

    protected function setUp(): void
    {
        $this->sut = new CapitalGoodAnswerHttpMapper();
    }

    public function testActivityTypeIsCapitalGood(): void
    {
        $this->assertSame(ActivityType::CapitalGood, $this->sut->activityType());
    }

    public function testMapsAValidRowToATypedAnswer(): void
    {
        $answer = $this->sut->map(['itemId' => 'capital-good-1', 'unit' => 'EUR', 'quantity' => '1500', 'acquisitionYear' => '2022']);

        $this->assertInstanceOf(CapitalGoodAnswer::class, $answer);
        $this->assertSame('capital-good-1', (string) $answer->capitalGoodId);
        $this->assertSame(Unit::Euro, $answer->quantity->unit());
        $this->assertSame(1500.0, $answer->quantity->value());
        $this->assertNull($answer->existingActivityId);
        $this->assertSame(2022, $answer->acquisitionYear);
    }

    public function testMapsAnEmptyAcquisitionYearToNull(): void
    {
        $answer = $this->sut->map(['itemId' => 'capital-good-1', 'unit' => 'EUR', 'quantity' => '10', 'acquisitionYear' => '']);

        $this->assertInstanceOf(CapitalGoodAnswer::class, $answer);
        $this->assertNull($answer->acquisitionYear);
    }

    public function testMapsANonNumericAcquisitionYearToNull(): void
    {
        $answer = $this->sut->map(['itemId' => 'capital-good-1', 'unit' => 'EUR', 'quantity' => '10', 'acquisitionYear' => 'abc']);

        $this->assertInstanceOf(CapitalGoodAnswer::class, $answer);
        $this->assertNull($answer->acquisitionYear);
    }

    public function testMapsTheExistingActivityId(): void
    {
        $answer = $this->sut->map(['itemId' => 'capital-good-1', 'unit' => 'USD', 'quantity' => '10', 'activityId' => 'activity-1']);

        $this->assertInstanceOf(CapitalGoodAnswer::class, $answer);
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
        $this->assertNull($this->sut->map(['itemId' => 'capital-good-1', 'unit' => 'EUR', 'quantity' => '-5']));
    }

    public function testReturnsNullWhenUnitIsInvalid(): void
    {
        $this->assertNull($this->sut->map(['itemId' => 'capital-good-1', 'unit' => 'invalid', 'quantity' => '10']));
    }
}
