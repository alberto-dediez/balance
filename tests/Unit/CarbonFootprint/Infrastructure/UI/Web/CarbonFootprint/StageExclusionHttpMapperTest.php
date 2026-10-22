<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprint\StageExclusionHttpMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class StageExclusionHttpMapperTest extends TestCase
{
    private StageExclusionHttpMapper $sut;

    protected function setUp(): void
    {
        $this->sut = new StageExclusionHttpMapper();
    }

    public function testMapsAnExcludedSource(): void
    {
        $request = new Request(request: ['exclusions' => [
            'refrigerant_leaks' => ['excluded' => '1', 'reason' => 'no_data_available', 'comment' => 'Sin datos'],
        ]]);

        $exclusions = $this->sut->map($request, Scope::Scope1);

        $this->assertCount(1, $exclusions);
        $this->assertSame(Source::RefrigerantLeaks, $exclusions[0]->source);
        $this->assertSame(ExclusionReason::NoDataAvailable, $exclusions[0]->reason);
        $this->assertSame('Sin datos', $exclusions[0]->comment);
    }

    public function testSkipsRowsWithoutTheExcludedFlag(): void
    {
        $request = new Request(request: ['exclusions' => [
            'refrigerant_leaks' => ['reason' => 'no_data_available'],
        ]]);

        $this->assertCount(0, $this->sut->map($request, Scope::Scope1));
    }

    public function testASourceOutsideTheStageThrows(): void
    {
        $request = new Request(request: ['exclusions' => [
            'building_electricity' => ['excluded' => '1', 'reason' => 'no_data_available'],
        ]]);

        $this->expectException(\InvalidArgumentException::class);

        $this->sut->map($request, Scope::Scope1);
    }

    public function testAnInvalidReasonThrows(): void
    {
        $request = new Request(request: ['exclusions' => [
            'refrigerant_leaks' => ['excluded' => '1', 'reason' => 'nope'],
        ]]);

        $this->expectException(\InvalidArgumentException::class);

        $this->sut->map($request, Scope::Scope1);
    }

    public function testMapsTheExclusionOfADerivedSource(): void
    {
        $request = new Request(request: ['exclusions' => [
            'fuel_and_energy_related' => ['excluded' => '1', 'reason' => 'not_significant'],
        ]]);

        $exclusions = $this->sut->map($request, Scope::Scope3);

        $this->assertCount(1, $exclusions);
        $this->assertSame(Source::FuelAndEnergyRelated, $exclusions[0]->source);
    }

    public function testMapsTheStageExclusion(): void
    {
        $request = new Request(request: ['stageExclusion' => ['excluded' => '1', 'reason' => 'no_data_available', 'comment' => '  Sin datos  ']]);

        $stageExclusion = $this->sut->mapStage($request, Scope::Scope3);

        $this->assertNotNull($stageExclusion);
        $this->assertSame(ExclusionReason::NoDataAvailable, $stageExclusion->reason);
        $this->assertSame('Sin datos', $stageExclusion->comment);
    }

    public function testTheStageIsNotExcludedWithoutTheExcludedFlag(): void
    {
        $request = new Request(request: ['stageExclusion' => ['reason' => 'no_data_available', 'comment' => '']]);

        $this->assertNull($this->sut->mapStage($request, Scope::Scope3));
        $this->assertNull($this->sut->mapStage(new Request(), Scope::Scope3));
    }

    public function testAnEmptyStageCommentBecomesNull(): void
    {
        $request = new Request(request: ['stageExclusion' => ['excluded' => '1', 'reason' => 'other', 'comment' => '   ']]);

        $this->assertNull($this->sut->mapStage($request, Scope::Scope3)?->comment);
    }

    public function testAnInvalidStageReasonThrows(): void
    {
        $request = new Request(request: ['stageExclusion' => ['excluded' => '1', 'reason' => 'nope']]);

        $this->expectException(\InvalidArgumentException::class);

        $this->sut->mapStage($request, Scope::Scope3);
    }
}
