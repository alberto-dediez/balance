<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View;

use App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase\GetPhaseResponse;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase\PhaseSection;
use App\CarbonFootprint\Domain\Model\Activity\Activity;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\OvernightStay\CategoryOvernight;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\ActivityRowView;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\ActivityRowViewFactoryRegistry;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\AssemblyBreakdownStepView;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\CombustibleRowViewFactory;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\ElectricityRowViewFactory;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\MachineryFuelRowViewFactory;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\MobilityRowViewFactory;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\OvernightStayRowViewFactory;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\RoadTransportRowViewFactory;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\WasteRowViewFactory;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ElectricityActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\MachineryFuelActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayMother;
use PHPUnit\Framework\TestCase;

class AssemblyBreakdownStepViewTest extends TestCase
{
    private AssemblyBreakdownStepView $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->sut = new AssemblyBreakdownStepView(new ActivityRowViewFactoryRegistry([
            new CombustibleRowViewFactory(),
            new RoadTransportRowViewFactory(),
            new MachineryFuelRowViewFactory(),
            new ElectricityRowViewFactory(),
            new OvernightStayRowViewFactory(),
            new MobilityRowViewFactory(),
            new WasteRowViewFactory(),
        ]));
    }

    public function testBuildReturnsExactlyTheKeysConsumedByTheTemplate(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();

        $view = $this->sut->build($eventCarbonFootprint, $this->response());

        $this->assertSame(
            [
                'eventCarbonFootprint',
                'combustibles',
                'roadTransports',
                'machineryFuels',
                'electricityMarketers',
                'gdOFactors',
                'mobilities',
                'overnightStays',
                'wastes',
                'units',
                'combustibleRows',
                'roadTransportRows',
                'machineryFuelRows',
                'machineryElectricityRows',
                'buildingElectricityRows',
                'overnightStayRows',
                'mobilityRows',
                'wasteRows',
            ],
            array_keys($view),
        );
        $this->assertSame($eventCarbonFootprint, $view['eventCarbonFootprint']);
        $this->assertSame(Unit::cases(), $view['units']);
    }

    public function testElectricityMarketersAndGdOFactorsComeFromTheMachineryElectricitySection(): void
    {
        $electricityMarketer = ElectricityMarketerMother::registeredElectricityMarketer();
        $gdOFactor = GdOFactorMother::registeredGdOFactor();

        $view = $this->sut->build(
            EventCarbonFootprintMother::registeredEventCarbonFootprint(),
            $this->response(electricityCatalog: [$electricityMarketer], gdOFactorsCatalog: [$gdOFactor]),
        );

        $this->assertSame([$electricityMarketer], $view['electricityMarketers']);
        $this->assertSame([$gdOFactor], $view['gdOFactors']);
    }

    public function testElectricityRowsCarryTheItemId2AndCo2eqAsString(): void
    {
        $activity = ElectricityActivityMother::registered();

        $view = $this->sut->build(
            EventCarbonFootprintMother::registeredEventCarbonFootprint(),
            $this->response(machineryElectricityActivities: [$activity]),
        );

        $rows = $view['machineryElectricityRows'] ?? null;
        $this->assertIsArray($rows);
        $this->assertCount(1, $rows);

        $row = $rows[0];
        $this->assertInstanceOf(ActivityRowView::class, $row);
        $this->assertSame((string) $activity->id(), $row->activityId);
        $this->assertSame((string) $activity->electricityMarketerId(), $row->itemId);
        $this->assertSame((string) $activity->gdOFactorId(), $row->itemId2);
        $this->assertSame($activity->quantity()->unit()->value, $row->unit);
        $this->assertSame($activity->quantity()->value(), $row->quantity);
        $this->assertSame($activity->emissions()->co2eq(), $row->co2eq);
        $this->assertNull($row->condition);

        $this->assertSame([], $view['buildingElectricityRows']);
    }

    public function testMachineryFuelRowsAreBuiltFromTheirOwnSection(): void
    {
        $activity = MachineryFuelActivityMother::registered();

        $view = $this->sut->build(
            EventCarbonFootprintMother::registeredEventCarbonFootprint(),
            $this->response(machineryFuelActivities: [$activity]),
        );

        $rows = $view['machineryFuelRows'] ?? null;
        $this->assertIsArray($rows);
        $this->assertCount(1, $rows);

        $row = $rows[0];
        $this->assertInstanceOf(ActivityRowView::class, $row);
        $this->assertSame((string) $activity->machineryFuelId(), $row->itemId);
        $this->assertNull($row->itemId2);
        $this->assertNull($row->condition);

        $this->assertSame([], $view['machineryElectricityRows']);
    }

    public function testOvernightStaysOnlyIncludeOrganizingTeamAndSuppliersCategories(): void
    {
        $organizingTeam = OvernightStayMother::from(name: 'organizing-team', id: (string) OvernightStayId::generate(), category: CategoryOvernight::OrganizingTeam);
        $suppliers = OvernightStayMother::from(name: 'suppliers', id: (string) OvernightStayId::generate(), category: CategoryOvernight::Suppliers);
        $attendees = OvernightStayMother::from(name: 'attendees', id: (string) OvernightStayId::generate(), category: CategoryOvernight::Attendees);

        $view = $this->sut->build(
            EventCarbonFootprintMother::registeredEventCarbonFootprint(),
            $this->response(overnightStayCatalog: [$organizingTeam, $suppliers, $attendees]),
        );

        $this->assertSame([$organizingTeam, $suppliers], $view['overnightStays']);
    }

    /**
     * @param list<Activity> $machineryFuelActivities
     * @param list<Activity> $machineryElectricityActivities
     * @param list<object>   $electricityCatalog
     * @param list<object>   $gdOFactorsCatalog
     * @param list<object>   $overnightStayCatalog
     */
    private function response(
        array $machineryFuelActivities = [],
        array $machineryElectricityActivities = [],
        array $electricityCatalog = [],
        array $gdOFactorsCatalog = [],
        array $overnightStayCatalog = [],
    ): GetPhaseResponse {
        $sections = Phase::Assembly->sections();

        return new GetPhaseResponse([
            new PhaseSection($sections[0], [], []),
            new PhaseSection($sections[1], [], []),
            new PhaseSection($sections[2], $machineryFuelActivities, []),
            new PhaseSection($sections[3], $machineryElectricityActivities, $electricityCatalog, $gdOFactorsCatalog),
            new PhaseSection($sections[4], [], $electricityCatalog, $gdOFactorsCatalog),
            new PhaseSection($sections[5], [], $overnightStayCatalog),
            new PhaseSection($sections[6], [], []),
            new PhaseSection($sections[7], [], []),
        ]);
    }
}
