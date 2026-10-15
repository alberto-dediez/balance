<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\PurchaseServiceAnswer;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\PurchaseServiceAnswerHttpMapper;
use PHPUnit\Framework\TestCase;

class PurchaseServiceAnswerHttpMapperTest extends TestCase
{
    private PurchaseServiceAnswerHttpMapper $sut;

    protected function setUp(): void
    {
        $this->sut = new PurchaseServiceAnswerHttpMapper();
    }

    public function testActivityTypeIsPurchaseService(): void
    {
        $this->assertSame(ActivityType::PurchaseService, $this->sut->activityType());
    }

    public function testMapsAValidRowToATypedAnswer(): void
    {
        $answer = $this->sut->map(['itemId' => 'service-1', 'unit' => 'EUR', 'quantity' => '1500']);

        $this->assertInstanceOf(PurchaseServiceAnswer::class, $answer);
        $this->assertSame('service-1', (string) $answer->purchaseServiceId);
        $this->assertSame(Unit::Euro, $answer->quantity->unit());
        $this->assertSame(1500.0, $answer->quantity->value());
        $this->assertNull($answer->existingActivityId);
    }

    public function testMapsTheExistingActivityId(): void
    {
        $answer = $this->sut->map(['itemId' => 'service-1', 'unit' => 'USD', 'quantity' => '10', 'activityId' => 'activity-1']);

        $this->assertInstanceOf(PurchaseServiceAnswer::class, $answer);
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
        $this->assertNull($this->sut->map(['itemId' => 'service-1', 'unit' => 'EUR', 'quantity' => '-5']));
    }

    public function testReturnsNullWhenUnitIsInvalid(): void
    {
        $this->assertNull($this->sut->map(['itemId' => 'service-1', 'unit' => 'invalid', 'quantity' => '10']));
    }
}
