<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View;

use App\CarbonFootprint\Application\CarbonFootprint\StageSection;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase\GetPhaseResponse;
use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\ActivityRowView;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\ActivityRowViewFactoryRegistry;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\CateringItemRowViewFactory;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\MaterialRowViewFactory;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\MaterialsStepView;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\MerchandisingItemRowViewFactory;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\MaterialActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Material\MaterialMother;
use PHPUnit\Framework\TestCase;

class MaterialsStepViewTest extends TestCase
{
    private MaterialsStepView $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->sut = new MaterialsStepView(new ActivityRowViewFactoryRegistry([
            new MaterialRowViewFactory(),
            new CateringItemRowViewFactory(),
            new MerchandisingItemRowViewFactory(),
        ]));
    }

    public function testBuildReturnsExactlyTheKeysConsumedByTheTemplate(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();

        $view = $this->sut->build($eventCarbonFootprint, $this->response());

        $this->assertSame(
            ['eventCarbonFootprint', 'activityRows', 'materials', 'cateringItems', 'merchandisingItems', 'units', 'conditions', 'exclusions', 'exclusionReasons'],
            array_keys($view),
        );
        $this->assertIsArray($view['activityRows']);
        $this->assertSame(
            [ActivityType::Material->value, ActivityType::CateringItem->value, ActivityType::MerchandisingItem->value],
            array_keys($view['activityRows']),
        );
        $this->assertSame($eventCarbonFootprint, $view['eventCarbonFootprint']);
        $this->assertSame(Unit::cases(), $view['units']);
        $this->assertSame(ItemCondition::cases(), $view['conditions']);
    }

    public function testBuildExposesTheSectionCatalogs(): void
    {
        $material = MaterialMother::registeredMaterial();

        $view = $this->sut->build(EventCarbonFootprintMother::registeredEventCarbonFootprint(), $this->response(materialsCatalog: [$material]));

        $this->assertSame([$material], $view['materials']);
        $this->assertSame([], $view['cateringItems']);
        $this->assertSame([], $view['merchandisingItems']);
    }

    public function testMaterialRowsCarryTheConditionAndCo2eqAsString(): void
    {
        $activity = MaterialActivityMother::registered();

        $view = $this->sut->build(EventCarbonFootprintMother::registeredEventCarbonFootprint(), $this->response(materialActivities: [$activity]));

        $this->assertIsArray($view['activityRows']);
        $rows = $view['activityRows'][ActivityType::Material->value] ?? null;
        $this->assertIsArray($rows);
        $this->assertCount(1, $rows);

        $row = $rows[0];
        $this->assertInstanceOf(ActivityRowView::class, $row);
        $this->assertSame((string) $activity->id(), $row->activityId);
        $this->assertSame((string) $activity->materialId(), $row->itemId);
        $this->assertSame($activity->condition()->value, $row->condition);
        $this->assertSame($activity->quantity()->unit()->value, $row->unit);
        $this->assertSame($activity->quantity()->value(), $row->quantity);
        $this->assertSame($activity->emissions()->co2eq(), $row->co2eq);
        $this->assertNull($row->itemId2);
    }

    /**
     * @param list<Activity> $materialActivities
     * @param list<object>   $materialsCatalog
     */
    private function response(array $materialActivities = [], array $materialsCatalog = []): GetPhaseResponse
    {
        $sections = Phase::Materials->sections();

        return new GetPhaseResponse([
            new StageSection($sections[0], $materialActivities, $materialsCatalog),
            new StageSection($sections[1], [], []),
            new StageSection($sections[2], [], []),
        ]);
    }
}
