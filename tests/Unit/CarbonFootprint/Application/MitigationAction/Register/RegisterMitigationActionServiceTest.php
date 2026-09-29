<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MitigationAction\Register;

use App\CarbonFootprint\Application\MitigationAction\MitigationPlanNotEditableException;
use App\CarbonFootprint\Application\MitigationAction\Register\RegisterMitigationActionRequest;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasure;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Application\MitigationAction\MitigationPlanContextTrait;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RegisterMitigationActionServiceTest extends TestCase
{
    use MitigationPlanContextTrait;

    protected function setUp(): void
    {
        FakeLocaleSetter::reset();
        DomainEventPublisher::instance()->reset();
        $this->initMitigationPlan();
    }

    public function testRegistersAManualActionInTheDefaultLocale(): void
    {
        $footprint = $this->propertyFootprint();

        $response = ($this->registerService())($this->manualRequest($footprint, 'Paneles solares'), $this->owner());

        $action = $this->mitigationActions->find($response->mitigationActionId);
        $this->assertNotNull($action);
        $this->assertTrue($action->isManual());
        $this->assertSame('Paneles solares', $action->defaultName());
        $this->assertSame(2025, $action->baseYear());
        $this->assertSame(2030, $action->targetYear());
        $this->assertTrue((new AccountId('account-id'))->equals($action->accountId() ?? new AccountId('none')));
        $this->assertCount(0, $action->translations());
        $this->assertCount(0, $action->effects());
    }

    public function testRegistersAManualActionInAnotherLocale(): void
    {
        $footprint = $this->propertyFootprint();
        FakeLocaleSetter::set(Locale::English);

        $response = ($this->registerService())($this->manualRequest($footprint, 'Solar panels'), $this->owner());

        $action = $this->mitigationActions->find($response->mitigationActionId);
        $this->assertNotNull($action);
        $this->assertSame('Solar panels', $action->name());
        $this->assertNotNull($action->translations()->getTranslationFor(Locale::English));
    }

    public function testRegistersFromAMeasureGeneratingEffectsWithTheMinimumAndCopyingTranslations(): void
    {
        $footprint = $this->propertyFootprint();
        $measure = $this->measure();

        $response = ($this->registerService())($this->measureRequest($footprint, $measure), $this->owner());

        $action = $this->mitigationActions->find($response->mitigationActionId);
        $this->assertNotNull($action);
        $this->assertFalse($action->isManual());
        $this->assertSame('Caldera eficiente', $action->defaultName());
        $this->assertSame('Sustituir la caldera', $action->defaultDescription());
        $this->assertCount(2, $action->translations());
        $this->assertCount(1, $action->effects());
        $effect = $action->effects()->forSourceAndItem(Source::FixedFacilities, (string) $this->naturalGasId);
        $this->assertNotNull($effect);
        $this->assertSame(0.1, $effect->reduction());
        $this->assertSame(0.1, $effect->minimumReduction());
        $this->assertSame(0.3, $effect->maximumReduction());
    }

    public function testRegistersFromAMeasureWithoutSourcesInTheFootprint(): void
    {
        $footprint = $this->propertyFootprint();
        $measure = MitigationMeasureMother::withEffect(Source::RefrigerantLeaks, 0.1, 0.3);
        $this->mitigationMeasures->add($measure);

        $response = ($this->registerService())($this->measureRequest($footprint, $measure), $this->owner());

        $this->assertCount(0, $this->mitigationActions->find($response->mitigationActionId)?->effects() ?? []);
    }

    public function testRejectsACompletedFootprint(): void
    {
        $footprint = $this->propertyFootprint();
        $footprint->complete(new Emissions('1500', '1500', '0', '0'));

        $this->expectException(MitigationPlanNotEditableException::class);

        ($this->registerService())($this->manualRequest($footprint, 'Paneles'), $this->owner());
    }

    public function testRejectsAnOwnerOfAnotherAccount(): void
    {
        $footprint = $this->propertyFootprint();

        $this->expectException(UnauthorizedActionException::class);

        ($this->registerService())($this->manualRequest($footprint, 'Paneles'), $this->owner('other-account'));
    }

    public function testRejectsAUserRole(): void
    {
        $footprint = $this->propertyFootprint();

        $this->expectException(UnauthorizedActionException::class);

        ($this->registerService())(
            $this->manualRequest($footprint, 'Paneles'),
            UserMother::fromUsernameAndRoleAndAccount('user', Role::User, new AccountId('account-id')),
        );
    }

    public function testRejectsEventFootprints(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ($this->registerService())(
            new RegisterMitigationActionRequest(CarbonFootprintReportSubjectType::Event, 'footprint-id', null, 'Paneles', '', 2030),
            $this->owner(),
        );
    }

    public function testRejectsAManualActionWithoutName(): void
    {
        $footprint = $this->propertyFootprint();

        $this->expectException(\InvalidArgumentException::class);

        ($this->registerService())($this->manualRequest($footprint, ' '), $this->owner());
    }

    private function manualRequest(PropertyCarbonFootprint $footprint, string $name): RegisterMitigationActionRequest
    {
        return new RegisterMitigationActionRequest(
            subjectType: CarbonFootprintReportSubjectType::Property,
            carbonFootprintId: (string) $footprint->id(),
            measureId: null,
            name: $name,
            description: 'description',
            targetYear: 2030,
        );
    }

    private function measureRequest(PropertyCarbonFootprint $footprint, MitigationMeasure $measure): RegisterMitigationActionRequest
    {
        return new RegisterMitigationActionRequest(
            subjectType: CarbonFootprintReportSubjectType::Property,
            carbonFootprintId: (string) $footprint->id(),
            measureId: $measure->id(),
            name: '',
            description: '',
            targetYear: 2030,
        );
    }

    private function measure(): MitigationMeasure
    {
        $measure = MitigationMeasureMother::withEffect(Source::FixedFacilities, 0.1, 0.3, 'Caldera eficiente');
        $measure->update('Caldera eficiente', 'Sustituir la caldera', $measure->categoryId() ?? throw new \LogicException());
        $measure->translate('Efficient boiler', 'Replace the boiler', Locale::English);
        $measure->translate('Caldaia efficiente', 'Sostituire la caldaia', Locale::Italian);
        $this->mitigationMeasures->add($measure);

        return $measure;
    }
}
