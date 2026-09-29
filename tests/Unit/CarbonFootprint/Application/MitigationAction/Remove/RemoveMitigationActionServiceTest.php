<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MitigationAction\Remove;

use App\CarbonFootprint\Application\MitigationAction\MitigationPlanNotEditableException;
use App\CarbonFootprint\Application\MitigationAction\Remove\RemoveMitigationActionRequest;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\MitigationAction\MitigationPlanContextTrait;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionMother;
use PHPUnit\Framework\TestCase;

class RemoveMitigationActionServiceTest extends TestCase
{
    use MitigationPlanContextTrait;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->initMitigationPlan();
    }

    public function testRemovesTheAction(): void
    {
        $footprint = $this->propertyFootprint();
        $action = MitigationActionMother::manual(carbonFootprintId: (string) $footprint->id());
        $this->mitigationActions->add($action);

        ($this->removeService())(new RemoveMitigationActionRequest($action->id()), $this->owner());

        $this->assertTrue($action->isRemoved());
        $this->assertNull($this->mitigationActions->find($action->id()));
    }

    public function testRejectsAnOwnerOfAnotherAccount(): void
    {
        $footprint = $this->propertyFootprint();
        $action = MitigationActionMother::manual(carbonFootprintId: (string) $footprint->id());
        $this->mitigationActions->add($action);

        $this->expectException(UnauthorizedActionException::class);

        ($this->removeService())(new RemoveMitigationActionRequest($action->id()), $this->owner('other-account'));
    }

    public function testRejectsACompletedFootprint(): void
    {
        $footprint = $this->propertyFootprint();
        $footprint->complete(new Emissions('1500', '1500', '0', '0'));
        $action = MitigationActionMother::manual(carbonFootprintId: (string) $footprint->id());
        $this->mitigationActions->add($action);

        $this->expectException(MitigationPlanNotEditableException::class);

        ($this->removeService())(new RemoveMitigationActionRequest($action->id()), $this->owner());
    }
}
