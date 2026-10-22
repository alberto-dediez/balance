<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint;

use App\CarbonFootprint\Infrastructure\UI\Web\CompanyCarbonFootprint\NonConsolidatedPropertyHttpMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class NonConsolidatedPropertyHttpMapperTest extends TestCase
{
    private NonConsolidatedPropertyHttpMapper $sut;

    protected function setUp(): void
    {
        $this->sut = new NonConsolidatedPropertyHttpMapper();
    }

    public function testAnEmptyValueMeansNoManualEmissions(): void
    {
        $answers = $this->sut->map(new Request(request: ['nonConsolidated' => ['property-1' => ['co2eq' => '']]]));

        $this->assertCount(1, $answers);
        $this->assertSame('property-1', (string) $answers[0]->propertyId);
        $this->assertNull($answers[0]->manualCo2eq);
    }

    public function testAcceptsADecimalComma(): void
    {
        $answers = $this->sut->map(new Request(request: ['nonConsolidated' => ['property-1' => ['co2eq' => '4500,25']]]));

        $this->assertSame('4500.25', $answers[0]->manualCo2eq);
    }

    public function testWithoutRowsReturnsNothing(): void
    {
        $this->assertSame([], $this->sut->map(new Request()));
    }

    public function testRejectsNegativeEmissions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid emissions for property "property-1"');

        $this->sut->map(new Request(request: ['nonConsolidated' => ['property-1' => ['co2eq' => '-1']]]));
    }

    public function testRejectsText(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->sut->map(new Request(request: ['nonConsolidated' => ['property-1' => ['co2eq' => 'mucho']]]));
    }
}
