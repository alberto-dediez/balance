<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MitigationAction\RemoveOfCarbonFootprint;

use App\CarbonFootprint\Application\MitigationAction\RemoveOfCarbonFootprint\RemoveMitigationActionsOfCarbonFootprintRequest;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\MitigationAction\MitigationPlanContextTrait;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RemoveMitigationActionsOfCarbonFootprintServiceTest extends TestCase
{
    use MitigationPlanContextTrait;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->initMitigationPlan();
    }

    public function testRemovesEveryActionOfTheFootprintEvenIfCompleted(): void
    {
        $footprint = $this->propertyFootprint();
        $footprint->complete(new Emissions('1500', '1500', '0', '0'));
        $first = MitigationActionMother::manual(carbonFootprintId: (string) $footprint->id());
        $second = MitigationActionMother::manual(carbonFootprintId: (string) $footprint->id());
        $other = MitigationActionMother::manual(carbonFootprintId: 'other-footprint');
        $this->mitigationActions->add($first);
        $this->mitigationActions->add($second);
        $this->mitigationActions->add($other);

        ($this->removeOfCarbonFootprintService())(
            new RemoveMitigationActionsOfCarbonFootprintRequest(CarbonFootprintReportSubjectType::Property, (string) $footprint->id()),
            $this->owner(),
        );

        $this->assertTrue($first->isRemoved());
        $this->assertTrue($second->isRemoved());
        $this->assertFalse($other->isRemoved());
    }

    public function testAnAdminCanRemoveThem(): void
    {
        $action = MitigationActionMother::manual(carbonFootprintId: 'footprint-id');
        $this->mitigationActions->add($action);

        ($this->removeOfCarbonFootprintService())(
            new RemoveMitigationActionsOfCarbonFootprintRequest(CarbonFootprintReportSubjectType::Property, 'footprint-id'),
            UserMother::adminUser(),
        );

        $this->assertTrue($action->isRemoved());
    }
}
