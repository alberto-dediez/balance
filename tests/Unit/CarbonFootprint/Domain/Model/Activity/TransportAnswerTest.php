<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\Activity\Answer\TransportAnswer;
use App\CarbonFootprint\Domain\Model\IncompatibleUnitsException;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Unit;
use PHPUnit\Framework\TestCase;

class TransportAnswerTest extends TestCase
{
    public function testFromWeightInKilogramsAndDistance(): void
    {
        $answer = TransportAnswer::fromWeightAndDistance(TransportId::generate(), new Quantity(2500.0, Unit::Kilogram), 350.0);

        $this->assertEqualsWithDelta(875.0, $answer->quantity->value(), 0.0001);
        $this->assertSame(Unit::TonneKilometre, $answer->quantity->unit());
    }

    public function testFromWeightInTonnesAndDistance(): void
    {
        $answer = TransportAnswer::fromWeightAndDistance(TransportId::generate(), new Quantity(2.5, Unit::Tonne), 350.0);

        $this->assertEqualsWithDelta(875.0, $answer->quantity->value(), 0.0001);
        $this->assertSame(Unit::TonneKilometre, $answer->quantity->unit());
    }

    public function testFromWeightAndZeroDistance(): void
    {
        $answer = TransportAnswer::fromWeightAndDistance(TransportId::generate(), new Quantity(800.0, Unit::Kilogram), 0.0);

        $this->assertSame(0.0, $answer->quantity->value());
        $this->assertSame(Unit::TonneKilometre, $answer->quantity->unit());
    }

    public function testFromWeightInLitresThrows(): void
    {
        $this->expectException(IncompatibleUnitsException::class);

        TransportAnswer::fromWeightAndDistance(TransportId::generate(), new Quantity(100.0, Unit::Liter), 350.0);
    }

    public function testKeepsWeightAndDistance(): void
    {
        $weight = new Quantity(2500.0, Unit::Kilogram);

        $answer = TransportAnswer::fromWeightAndDistance(TransportId::generate(), $weight, 350.0);

        $this->assertSame($weight, $answer->weight);
        $this->assertSame(350.0, $answer->distanceKm);
    }
}
