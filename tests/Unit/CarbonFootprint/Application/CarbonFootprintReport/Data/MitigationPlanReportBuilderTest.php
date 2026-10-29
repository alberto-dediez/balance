<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\Data;

use App\CarbonFootprint\Domain\Model\Activity\CombustibleActivity;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationAction;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use PHPUnit\Framework\TestCase;

class MitigationPlanReportBuilderTest extends TestCase
{
    use OrganizationalCollectorContextTrait;

    private PropertyCarbonFootprint $footprint;
    private string $combustibleId;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->initActivities();

        $this->footprint = PropertyCarbonFootprintMother::withEmployeesAnswered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id', 2025);
        $activity = $this->combustibleActivity('1000');
        assert($activity instanceof CombustibleActivity);
        $this->combustibleId = (string) $activity->combustibleId();
        $this->footprint->addActivity($activity->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
    }

    public function testUsesTheTranslationOfTheReceivedLocale(): void
    {
        $action = $this->action();
        $action->translate('Efficient boiler', 'Replace it', Locale::English);

        $plan = $this->mitigationPlanBuilder()->build(CarbonFootprintReportSubjectType::Property, $this->footprint, Locale::English);

        $this->assertNotNull($plan);
        $this->assertSame('Efficient boiler', $plan->actions[0]->name);
        $this->assertSame('Replace it', $plan->actions[0]->description);
        $this->assertSame(2025, $plan->actions[0]->baseYear);
        $this->assertSame(2030, $plan->actions[0]->targetYear);
        $this->assertSame(Scope::Scope1, $plan->actions[0]->effects[0]['scope']);
        $this->assertSame(0.2, $plan->actions[0]->effects[0]['reduction']);
    }

    public function testFallsBackToTheDefaultTextsWithoutTranslation(): void
    {
        $action = $this->action();
        $action->translate('Efficient boiler', 'Replace it', Locale::English);

        $plan = $this->mitigationPlanBuilder()->build(CarbonFootprintReportSubjectType::Property, $this->footprint, Locale::Italian);

        $this->assertNotNull($plan);
        $this->assertSame('Caldera', $plan->actions[0]->name);
        $this->assertSame('action-description', $plan->actions[0]->description);
    }

    public function testFallsBackToTheDefaultTextsWithoutLocale(): void
    {
        $this->action();

        $plan = $this->mitigationPlanBuilder()->build(CarbonFootprintReportSubjectType::Property, $this->footprint, null);

        $this->assertNotNull($plan);
        $this->assertSame('Caldera', $plan->actions[0]->name);
    }

    public function testAnActionOnASourceAlsoReducesItsWellToTank(): void
    {
        $activity = $this->combustibleActivityWithWellToTank('500', '50');
        assert($activity instanceof CombustibleActivity);
        $footprint = PropertyCarbonFootprintMother::withEmployeesAnswered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id', 2025);
        $footprint->addActivity($activity->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));
        $action = MitigationActionMother::manual(carbonFootprintId: (string) $footprint->id(), name: 'Caldera');
        $action->setEffect(Source::FixedFacilities, (string) $activity->combustibleId(), 'Gas natural', 0.2);
        $this->mitigationActionRepository->add($action);

        $plan = $this->mitigationPlanBuilder()->build(CarbonFootprintReportSubjectType::Property, $footprint, null);

        $this->assertNotNull($plan);
        $this->assertCount(2, $plan->projection->sources);
        $wellToTank = $plan->projection->sources[1];
        $this->assertSame(Scope::Scope3, $wellToTank->emissionSource->scope);
        $this->assertSame(Source::FuelAndEnergyRelated, $wellToTank->emissionSource->source);
        $this->assertTrue($wellToTank->hasReductions);
        $this->assertEqualsWithDelta(40.0, $wellToTank->expectedByYear[2030], 0.001);
        $this->assertEqualsWithDelta(550.0, $plan->projection->totalByYear[2025], 0.001);
        $this->assertEqualsWithDelta(440.0, $plan->projection->totalByYear[2030], 0.001);
    }

    public function testReturnsNullWithoutActions(): void
    {
        $this->assertNull($this->mitigationPlanBuilder()->build(CarbonFootprintReportSubjectType::Property, $this->footprint, Locale::Spanish));
    }

    private function action(): MitigationAction
    {
        $action = MitigationActionMother::manual(carbonFootprintId: (string) $this->footprint->id(), name: 'Caldera');
        $action->setEffect(Source::FixedFacilities, $this->combustibleId, 'Gas natural', 0.2);
        $this->mitigationActionRepository->add($action);

        return $action;
    }
}
