<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\RefrigerantGasAnswer;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\RefrigerantGasAnswerHttpMapper;
use PHPUnit\Framework\TestCase;

class RefrigerantGasAnswerHttpMapperTest extends TestCase
{
    private RefrigerantGasAnswerHttpMapper $sut;

    protected function setUp(): void
    {
        $this->sut = new RefrigerantGasAnswerHttpMapper();
    }

    public function testActivityTypeIsRefrigerantGas(): void
    {
        $this->assertSame(ActivityType::RefrigerantGas, $this->sut->activityType());
    }

    public function testMapsAValidRowToATypedAnswer(): void
    {
        $answer = $this->sut->map(['itemId' => 'gas-1', 'unit' => 'kg', 'quantity' => '10', 'source' => 'Sala de máquinas', 'activityId' => 'activity-1']);

        $this->assertInstanceOf(RefrigerantGasAnswer::class, $answer);
        $this->assertSame('gas-1', (string) $answer->refrigerantGasId);
        $this->assertSame(Unit::Kilogram, $answer->quantity->unit());
        $this->assertSame(10.0, $answer->quantity->value());
        $this->assertSame('Sala de máquinas', $answer->source);
        $this->assertNotNull($answer->existingActivityId);
        $this->assertSame('activity-1', (string) $answer->existingActivityId);
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
        $this->assertNull($this->sut->map(['itemId' => 'gas-1', 'unit' => 'invalid', 'quantity' => '10']));
    }

    public function testReturnsNullWhenQuantityIsNegative(): void
    {
        $this->assertNull($this->sut->map(['itemId' => 'gas-1', 'unit' => 'kg', 'quantity' => '-5']));
    }

    public function testMapsAValidRowWithZeroQuantity(): void
    {
        $answer = $this->sut->map(['itemId' => 'gas-1', 'unit' => 'kg', 'quantity' => '0']);

        $this->assertInstanceOf(RefrigerantGasAnswer::class, $answer);
        $this->assertSame(0.0, $answer->quantity->value());
    }

    public function testReturnsNullWhenUnitIsNotOfMassMagnitude(): void
    {
        $this->assertNull($this->sut->map(['itemId' => 'gas-1', 'unit' => 'l', 'quantity' => '10']));
    }

    public function testAcceptsGrams(): void
    {
        $answer = $this->sut->map(['itemId' => 'gas-1', 'unit' => 'g', 'quantity' => '500']);

        $this->assertInstanceOf(RefrigerantGasAnswer::class, $answer);
        $this->assertSame(Unit::Gram, $answer->quantity->unit());
    }

    public function testAcceptsTonnes(): void
    {
        $answer = $this->sut->map(['itemId' => 'gas-1', 'unit' => 't', 'quantity' => '2']);

        $this->assertInstanceOf(RefrigerantGasAnswer::class, $answer);
        $this->assertSame(Unit::Tonne, $answer->quantity->unit());
    }
}
