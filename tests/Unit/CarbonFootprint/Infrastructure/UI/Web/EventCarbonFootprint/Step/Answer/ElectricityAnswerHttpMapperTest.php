<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ElectricityAnswer;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\ElectricityAnswerHttpMapper;
use PHPUnit\Framework\TestCase;

class ElectricityAnswerHttpMapperTest extends TestCase
{
    private ElectricityAnswerHttpMapper $sut;

    protected function setUp(): void
    {
        $this->sut = new ElectricityAnswerHttpMapper();
    }

    public function testActivityTypeIsElectricity(): void
    {
        $this->assertSame(ActivityType::Electricity, $this->sut->activityType());
    }

    public function testMapsAValidRowToATypedAnswer(): void
    {
        $answer = $this->sut->map(['itemId' => 'marketer-1', 'itemId2' => 'gdo-1', 'unit' => 'kWh', 'quantity' => '100']);

        $this->assertInstanceOf(ElectricityAnswer::class, $answer);
        $this->assertSame('marketer-1', (string) $answer->electricityMarketerId);
        $this->assertSame('gdo-1', (string) $answer->gdOFactorId);
        $this->assertSame(Unit::KWh, $answer->quantity->unit());
        $this->assertSame(100.0, $answer->quantity->value());
        $this->assertNull($answer->existingActivityId);
    }

    public function testMapsAValidRowWithoutAGdOFactor(): void
    {
        $answer = $this->sut->map(['itemId' => 'marketer-1', 'unit' => 'kWh', 'quantity' => '100']);

        $this->assertInstanceOf(ElectricityAnswer::class, $answer);
        $this->assertSame('marketer-1', (string) $answer->electricityMarketerId);
        $this->assertNull($answer->gdOFactorId);
    }

    public function testMapsAValidRowWithoutAnElectricityMarketer(): void
    {
        $answer = $this->sut->map(['itemId' => '', 'itemId2' => 'gdo-1', 'unit' => 'kWh', 'quantity' => '100']);

        $this->assertInstanceOf(ElectricityAnswer::class, $answer);
        $this->assertNull($answer->electricityMarketerId);
        $this->assertSame('gdo-1', (string) $answer->gdOFactorId);
    }

    public function testReturnsNullWhenNeitherItemIdNorItemId2AreProvided(): void
    {
        $this->assertNull($this->sut->map(['itemId' => '', 'itemId2' => '', 'unit' => 'kWh', 'quantity' => '100']));
    }

    public function testReturnsNullWhenUnitIsInvalid(): void
    {
        $this->assertNull($this->sut->map(['itemId' => 'marketer-1', 'itemId2' => 'gdo-1', 'unit' => 'invalid', 'quantity' => '100']));
    }

    public function testReturnsNullWhenQuantityIsNegative(): void
    {
        $this->assertNull($this->sut->map(['itemId' => 'marketer-1', 'itemId2' => 'gdo-1', 'unit' => 'kWh', 'quantity' => '-5']));
    }

    public function testMapsAValidRowWithZeroQuantity(): void
    {
        $answer = $this->sut->map(['itemId' => 'marketer-1', 'itemId2' => 'gdo-1', 'unit' => 'kWh', 'quantity' => '0']);

        $this->assertInstanceOf(ElectricityAnswer::class, $answer);
        $this->assertSame(0.0, $answer->quantity->value());
    }

    public function testAcceptsCommaAsDecimalSeparator(): void
    {
        $answer = $this->sut->map(['itemId' => 'marketer-1', 'itemId2' => 'gdo-1', 'unit' => 'kWh', 'quantity' => '2,5']);

        $this->assertInstanceOf(ElectricityAnswer::class, $answer);
        $this->assertSame(2.5, $answer->quantity->value());
    }

    public function testAProvidedActivityIdMapsToAnExistingActivityId(): void
    {
        $answer = $this->sut->map(['itemId' => 'marketer-1', 'itemId2' => 'gdo-1', 'unit' => 'kWh', 'quantity' => '100', 'activityId' => 'activity-1']);

        $this->assertInstanceOf(ElectricityAnswer::class, $answer);
        $this->assertNotNull($answer->existingActivityId);
        $this->assertSame('activity-1', (string) $answer->existingActivityId);
    }

    public function testMapsAProvidedSource(): void
    {
        $answer = $this->sut->map(['itemId' => 'marketer-1', 'itemId2' => 'gdo-1', 'unit' => 'kWh', 'quantity' => '100', 'source' => 'Factura #123']);

        $this->assertInstanceOf(ElectricityAnswer::class, $answer);
        $this->assertSame('Factura #123', $answer->source);
    }

    public function testSourceIsNullWhenMissing(): void
    {
        $answer = $this->sut->map(['itemId' => 'marketer-1', 'itemId2' => 'gdo-1', 'unit' => 'kWh', 'quantity' => '100']);

        $this->assertInstanceOf(ElectricityAnswer::class, $answer);
        $this->assertNull($answer->source);
    }
}
