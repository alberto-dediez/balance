<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EventCarbonFootprint\Step;

use App\CarbonFootprint\Application\Activity\Table\ActivityTableAssemblerRegistry;
use App\CarbonFootprint\Application\Activity\Table\CateringItemTableAssembler;
use App\CarbonFootprint\Application\Activity\Table\MaterialTableAssembler;
use App\CarbonFootprint\Application\Activity\Table\MerchandisingItemTableAssembler;
use App\CarbonFootprint\Application\EventCarbonFootprint\Step\MaterialsStepViewBuilder;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepositoryMother;
use PHPUnit\Framework\TestCase;

class MaterialsStepViewBuilderTest extends TestCase
{
    private MaterialsStepViewBuilder $builder;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $registry = new ActivityTableAssemblerRegistry([
            new MaterialTableAssembler(MaterialRepositoryMother::inMemory()),
            new CateringItemTableAssembler(CateringItemRepositoryMother::inMemory()),
            new MerchandisingItemTableAssembler(MerchandisingItemRepositoryMother::inMemory()),
        ]);

        $this->builder = new MaterialsStepViewBuilder($registry, ActivityRepositoryMother::inMemory());
    }

    public function testBuildReturnsAllKeysConsumedByTheTemplate(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();

        $view = $this->builder->build($eventCarbonFootprint);

        $this->assertSame($eventCarbonFootprint, $view['eventCarbonFootprint']);
        $this->assertArrayHasKey('activityRows', $view);
        $this->assertArrayHasKey(ActivityType::Material->value, $view['activityRows']);
        $this->assertArrayHasKey(ActivityType::CateringItem->value, $view['activityRows']);
        $this->assertArrayHasKey(ActivityType::MerchandisingItem->value, $view['activityRows']);
        $this->assertIsArray($view['materials']);
        $this->assertIsArray($view['cateringItems']);
        $this->assertIsArray($view['merchandisingItems']);
        $this->assertSame(Unit::cases(), $view['units']);
        $this->assertSame(ItemCondition::cases(), $view['conditions']);
    }
}
