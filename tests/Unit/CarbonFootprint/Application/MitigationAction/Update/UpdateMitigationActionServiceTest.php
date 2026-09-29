<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MitigationAction\Update;

use App\CarbonFootprint\Application\MitigationAction\MitigationActionEffectData;
use App\CarbonFootprint\Application\MitigationAction\MitigationPlanNotEditableException;
use App\CarbonFootprint\Application\MitigationAction\Update\UpdateMitigationActionRequest;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationAction;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionId;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionReductionOutOfRangeException;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Application\MitigationAction\MitigationPlanContextTrait;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateMitigationActionServiceTest extends TestCase
{
    use MitigationPlanContextTrait;

    protected function setUp(): void
    {
        FakeLocaleSetter::reset();
        DomainEventPublisher::instance()->reset();
        $this->initMitigationPlan();
    }

    public function testManualActionSyncsEffects(): void
    {
        $footprint = $this->propertyFootprint();
        $action = $this->manualAction($footprint);
        $action->setEffect(Source::BuildingElectricity, (string) $this->marketerId, 'Marketer', 0.2);

        ($this->updateService())(new UpdateMitigationActionRequest(
            mitigationActionId: $action->id(),
            name: 'Caldera',
            description: 'Nueva',
            targetYear: 2031,
            effects: [new MitigationActionEffectData(Source::FixedFacilities, (string) $this->naturalGasId, 0.25)],
        ), $this->owner());

        $this->assertSame('Caldera', $action->name());
        $this->assertSame(2031, $action->targetYear());
        $this->assertCount(1, $action->effects());
        $this->assertSame(0.25, $action->effects()->forSourceAndItem(Source::FixedFacilities, (string) $this->naturalGasId)?->reduction());
    }

    public function testActionFromMeasureOnlyChangesReductions(): void
    {
        $footprint = $this->propertyFootprint();
        $action = MitigationActionMother::fromMeasure(MitigationMeasureId::generate(), (string) $footprint->id());
        $action->setEffect(Source::FixedFacilities, (string) $this->naturalGasId, 'Gas', 0.1, 0.1, 0.3);
        $action->setEffect(Source::BuildingElectricity, (string) $this->marketerId, 'Marketer', 0.1, 0.1, 0.3);
        $this->mitigationActions->add($action);

        ($this->updateService())(new UpdateMitigationActionRequest(
            mitigationActionId: $action->id(),
            name: 'Caldera',
            description: '',
            targetYear: 2030,
            effects: [new MitigationActionEffectData(Source::FixedFacilities, (string) $this->naturalGasId, 0.2)],
        ), $this->owner());

        $this->assertCount(2, $action->effects());
        $effect = $action->effects()->forSourceAndItem(Source::FixedFacilities, (string) $this->naturalGasId);
        $this->assertNotNull($effect);
        $this->assertSame(0.2, $effect->reduction());
        $this->assertSame(0.3, $effect->maximumReduction());
    }

    public function testEditingInAnotherLocaleStoresTheTranslation(): void
    {
        $footprint = $this->propertyFootprint();
        $action = $this->manualAction($footprint);
        FakeLocaleSetter::set(Locale::English);

        ($this->updateService())(new UpdateMitigationActionRequest($action->id(), 'Solar panels', 'Install', 2030), $this->owner());

        $this->assertSame('action-name', $action->defaultName());
        $this->assertSame('Solar panels', $action->name());
    }

    public function testRejectsAReductionOutOfRange(): void
    {
        $footprint = $this->propertyFootprint();
        $action = MitigationActionMother::fromMeasure(MitigationMeasureId::generate(), (string) $footprint->id());
        $action->setEffect(Source::FixedFacilities, (string) $this->naturalGasId, 'Gas', 0.1, 0.1, 0.3);
        $this->mitigationActions->add($action);

        $this->expectException(MitigationActionReductionOutOfRangeException::class);

        ($this->updateService())(new UpdateMitigationActionRequest(
            $action->id(),
            'Caldera',
            '',
            2030,
            [new MitigationActionEffectData(Source::FixedFacilities, (string) $this->naturalGasId, 0.5)],
        ), $this->owner());
    }

    public function testRejectsAnUnknownEmissionSource(): void
    {
        $footprint = $this->propertyFootprint();
        $action = $this->manualAction($footprint);

        $this->expectException(\InvalidArgumentException::class);

        ($this->updateService())(new UpdateMitigationActionRequest(
            $action->id(),
            'Caldera',
            '',
            2030,
            [new MitigationActionEffectData(Source::RefrigerantLeaks, 'unknown', 0.5)],
        ), $this->owner());
    }

    public function testRejectsACompletedFootprint(): void
    {
        $footprint = $this->propertyFootprint();
        $action = $this->manualAction($footprint);
        $footprint->complete(new Emissions('1500', '1500', '0', '0'));

        $this->expectException(MitigationPlanNotEditableException::class);

        ($this->updateService())(new UpdateMitigationActionRequest($action->id(), 'Caldera', '', 2030), $this->owner());
    }

    private function manualAction(PropertyCarbonFootprint $footprint): MitigationAction
    {
        $action = MitigationActionMother::from(id: (string) MitigationActionId::generate(), carbonFootprintId: (string) $footprint->id());
        $this->mitigationActions->add($action);

        return $action;
    }
}
