<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MitigationAction\GetPlan;

use App\CarbonFootprint\Application\MitigationAction\GetPlan\GetMitigationPlanRequest;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\MitigationAction\MitigationPlanContextTrait;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionMother;
use PHPUnit\Framework\TestCase;

class GetMitigationPlanServiceTest extends TestCase
{
    use MitigationPlanContextTrait;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->initMitigationPlan();
    }

    public function testReturnsActionsSourcesAndProjection(): void
    {
        $propertyId = PropertyId::generate();
        $this->propertyFootprint(2024, $propertyId);
        $footprint = $this->propertyFootprint(2025, $propertyId);
        $this->mitigationActions->add(MitigationActionMother::withEffect(Source::FixedFacilities, (string) $this->naturalGasId, 0.2, 2025, 2030, (string) $footprint->id()));

        $response = ($this->getPlanService())(new GetMitigationPlanRequest(CarbonFootprintReportSubjectType::Property, (string) $footprint->id()), $this->owner());

        $this->assertCount(1, $response->actions);
        $this->assertCount(2, $response->emissionSources);
        $this->assertNotNull($response->projection);
        $this->assertEqualsWithDelta(1300.0, $response->projection->totalByYear[2030], 0.001);
        $this->assertTrue($response->hasPreviousFootprint);
    }

    public function testWithoutActionsHasNoProjection(): void
    {
        $footprint = $this->propertyFootprint();

        $response = ($this->getPlanService())(new GetMitigationPlanRequest(CarbonFootprintReportSubjectType::Property, (string) $footprint->id()), $this->owner());

        $this->assertSame([], $response->actions);
        $this->assertNull($response->projection);
        $this->assertFalse($response->hasPreviousFootprint);
    }

    public function testRejectsAnOwnerOfAnotherAccount(): void
    {
        $footprint = $this->propertyFootprint();

        $this->expectException(UnauthorizedActionException::class);

        ($this->getPlanService())(new GetMitigationPlanRequest(CarbonFootprintReportSubjectType::Property, (string) $footprint->id()), $this->owner('other-account'));
    }
}
