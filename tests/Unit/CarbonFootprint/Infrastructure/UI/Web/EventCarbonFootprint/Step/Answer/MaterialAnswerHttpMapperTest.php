<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\MaterialAnswer;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\MaterialAnswerHttpMapper;
use PHPUnit\Framework\TestCase;

class MaterialAnswerHttpMapperTest extends TestCase
{
    private MaterialAnswerHttpMapper $sut;

    protected function setUp(): void
    {
        $this->sut = new MaterialAnswerHttpMapper();
    }

    public function testActivityTypeIsMaterial(): void
    {
        $this->assertSame(ActivityType::Material, $this->sut->activityType());
    }

    public function testMapsAValidRowToATypedAnswer(): void
    {
        $answer = $this->sut->map(['itemId' => 'material-1', 'condition' => 'recycled', 'unit' => 'kg', 'quantity' => '10']);

        $this->assertInstanceOf(MaterialAnswer::class, $answer);
        $this->assertSame('material-1', (string) $answer->materialId);
        $this->assertSame(ItemCondition::Recycled, $answer->condition);
        $this->assertSame(Unit::Kilogram, $answer->quantity->unit());
        $this->assertSame(10.0, $answer->quantity->value());
        $this->assertNull($answer->existingActivityId);
    }

    public function testReturnsNullWhenItemIdIsEmpty(): void
    {
        $this->assertNull($this->sut->map(['itemId' => '', 'unit' => 'kg', 'quantity' => '10']));
    }

    public function testReturnsNullWhenItemIdIsOnlyWhitespace(): void
    {
        $this->assertNull($this->sut->map(['itemId' => '   ', 'unit' => 'kg', 'quantity' => '10']));
    }

    public function testReturnsNullWhenUnitIsInvalid(): void
    {
        $this->assertNull($this->sut->map(['itemId' => 'material-1', 'unit' => 'invalid', 'quantity' => '10']));
    }

    public function testReturnsNullWhenQuantityIsNegative(): void
    {
        $this->assertNull($this->sut->map(['itemId' => 'material-2', 'unit' => 'kg', 'quantity' => '-5']));
    }

    public function testMapsAValidRowWithZeroQuantity(): void
    {
        $answer = $this->sut->map(['itemId' => 'material-1', 'unit' => 'kg', 'quantity' => '0']);

        $this->assertInstanceOf(MaterialAnswer::class, $answer);
        $this->assertSame(0.0, $answer->quantity->value());
    }

    public function testAcceptsCommaAsDecimalSeparator(): void
    {
        $answer = $this->sut->map(['itemId' => 'material-1', 'unit' => 'kg', 'quantity' => '2,5']);

        $this->assertInstanceOf(MaterialAnswer::class, $answer);
        $this->assertSame(2.5, $answer->quantity->value());
    }

    public function testFallsBackToNewConditionWhenConditionIsMissing(): void
    {
        $answer = $this->sut->map(['itemId' => 'material-1', 'unit' => 'kg', 'quantity' => '10']);

        $this->assertInstanceOf(MaterialAnswer::class, $answer);
        $this->assertSame(ItemCondition::New, $answer->condition);
    }

    public function testFallsBackToNewConditionWhenConditionIsInvalid(): void
    {
        $answer = $this->sut->map(['itemId' => 'material-1', 'condition' => 'invalid', 'unit' => 'kg', 'quantity' => '10']);

        $this->assertInstanceOf(MaterialAnswer::class, $answer);
        $this->assertSame(ItemCondition::New, $answer->condition);
    }

    public function testAnEmptyActivityIdMapsToANullExistingActivityId(): void
    {
        $answer = $this->sut->map(['itemId' => 'material-1', 'unit' => 'kg', 'quantity' => '10', 'activityId' => '']);

        $this->assertInstanceOf(MaterialAnswer::class, $answer);
        $this->assertNull($answer->existingActivityId);
    }

    public function testAProvidedActivityIdMapsToAnExistingActivityId(): void
    {
        $answer = $this->sut->map(['itemId' => 'material-1', 'unit' => 'kg', 'quantity' => '10', 'activityId' => 'activity-1']);

        $this->assertInstanceOf(MaterialAnswer::class, $answer);
        $this->assertNotNull($answer->existingActivityId);
        $this->assertSame('activity-1', (string) $answer->existingActivityId);
    }
}
