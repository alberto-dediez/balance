<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\Activity\Answer\CombustibleAnswer;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprint\StageAnswerHttpMapper;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\CombustibleAnswerHttpMapper;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\ElectricityAnswerHttpMapper;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\RefrigerantGasAnswerHttpMapper;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\RoadTransportAnswerHttpMapper;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\SectionAnswerHttpMapperRegistry;
use PHPUnit\Framework\TestCase;

class StageAnswerHttpMapperTest extends TestCase
{
    private StageAnswerHttpMapper $sut;

    protected function setUp(): void
    {
        $this->sut = new StageAnswerHttpMapper(new SectionAnswerHttpMapperRegistry([
            new CombustibleAnswerHttpMapper(),
            new RoadTransportAnswerHttpMapper(),
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
}
