<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\Answer\CombustibleAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\TransportAnswer;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprint\StageAnswerHttpMapper;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\CombustibleAnswerHttpMapper;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\ElectricityAnswerHttpMapper;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\RefrigerantGasAnswerHttpMapper;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\SectionAnswerHttpMapperRegistry;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\TransportAnswerHttpMapper;
use PHPUnit\Framework\TestCase;

class StageAnswerHttpMapperTest extends TestCase
{
    private StageAnswerHttpMapper $sut;

    protected function setUp(): void
    {
        $this->sut = new StageAnswerHttpMapper(new SectionAnswerHttpMapperRegistry([
            new CombustibleAnswerHttpMapper(),
            new TransportAnswerHttpMapper(),
            new RefrigerantGasAnswerHttpMapper(),
            new ElectricityAnswerHttpMapper(),
        ]));
    }

    public function testMapsFlatData(): void
    {
        $sections = $this->sut->map([
            'combustibles' => [['itemId' => 'c-1', 'unit' => 'kWh', 'quantity' => '1000']],
        ], Scope::Scope1->sections());

        $this->assertCount(3, $sections);
        $this->assertCount(1, $sections[0]->answers);
        $this->assertInstanceOf(CombustibleAnswer::class, $sections[0]->answers[0]);
        $this->assertCount(0, $sections[1]->answers);
        $this->assertCount(0, $sections[2]->answers);
    }

    public function testMapsNestedPropertyBlockData(): void
    {
        $data = ['properties' => ['P1' => ['combustibles' => [['itemId' => 'c-1', 'unit' => 'kWh', 'quantity' => '1000']]]]];

        $sections = $this->sut->map($data['properties']['P1'], Scope::Scope1->sections());

        $this->assertCount(1, $sections[0]->answers);
        $this->assertInstanceOf(CombustibleAnswer::class, $sections[0]->answers[0]);
    }

    public function testIgnoresNonArrayRows(): void
    {
        $sections = $this->sut->map(['combustibles' => ['not-an-array', ['itemId' => 'c-1', 'unit' => 'kWh', 'quantity' => '1000']]], Scope::Scope1->sections());

        $this->assertCount(1, $sections[0]->answers);
    }

    public function testMissingSectionKeyProducesAnEmptySection(): void
    {
        $sections = $this->sut->map([], Scope::Scope2->sections());

        $this->assertCount(1, $sections);
        $this->assertCount(0, $sections[0]->answers);
    }

    public function testMapsTransportRowWithWeightAndDistanceToTonneKilometre(): void
    {
        $sections = $this->sut->map([
            'vehicles' => [['itemId' => 't-1', 'weight' => '2500', 'weightUnit' => 'kg', 'distanceKm' => '350']],
        ], Scope::Scope1->sections());

        $answer = $sections[1]->answers[0];
        $this->assertInstanceOf(TransportAnswer::class, $answer);
        $this->assertEqualsWithDelta(875.0, $answer->quantity->value(), 0.0001);
        $this->assertSame(Unit::TonneKilometre, $answer->quantity->unit());
        $this->assertNotNull($answer->weight);
        $this->assertSame(2500.0, $answer->weight->value());
        $this->assertSame(Unit::Kilogram, $answer->weight->unit());
        $this->assertSame(350.0, $answer->distanceKm);
    }

    public function testMapsTransportRowWithDirectQuantity(): void
    {
        $sections = $this->sut->map([
            'vehicles' => [['itemId' => 't-1', 'quantity' => '100', 'unit' => 'kg']],
        ], Scope::Scope1->sections());

        $answer = $sections[1]->answers[0];
        $this->assertInstanceOf(TransportAnswer::class, $answer);
        $this->assertSame(100.0, $answer->quantity->value());
        $this->assertSame(Unit::Kilogram, $answer->quantity->unit());
        $this->assertNull($answer->weight);
        $this->assertNull($answer->distanceKm);
    }

    public function testTransportRowWithWeightButNoDistanceFallsBackToDirectQuantity(): void
    {
        $sections = $this->sut->map([
            'vehicles' => [['itemId' => 't-1', 'weight' => '2500', 'weightUnit' => 'kg', 'quantity' => '40', 'unit' => 'km']],
        ], Scope::Scope1->sections());

        $answer = $sections[1]->answers[0];
        $this->assertInstanceOf(TransportAnswer::class, $answer);
        $this->assertSame(40.0, $answer->quantity->value());
        $this->assertSame(Unit::Kilometer, $answer->quantity->unit());
        $this->assertNull($answer->weight);
        $this->assertNull($answer->distanceKm);
    }
}
