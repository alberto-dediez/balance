<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationAction;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionEffectAdded;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionEffectRemoved;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionEffectUpdated;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionId;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionInvalidYearsException;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionReductionOutOfRangeException;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionRegistered;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionRemoved;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionSourceNotSupportedException;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionTranslated;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class MitigationActionTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        FakeLocaleSetter::reset();
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRegisterPublishesEventAndSetsState(): void
    {
        $action = new MitigationAction(id: MitigationActionId::generate());
        $action->register(
            subjectType: CarbonFootprintReportSubjectType::Company,
            carbonFootprintId: 'footprint-1',
            accountId: new AccountId('account-id'),
            measureId: null,
            name: 'Solar panels',
            description: 'Install solar panels',
            baseYear: 2025,
            targetYear: 2030,
        );

        $this->assertInstanceOf(MitigationActionRegistered::class, $this->spySubscriber->lastEvent());
        $this->assertSame(CarbonFootprintReportSubjectType::Company, $action->subjectType());
        $this->assertSame('footprint-1', $action->carbonFootprintId());
        $this->assertSame('Solar panels', $action->name());
        $this->assertSame('Install solar panels', $action->description());
        $this->assertSame(2025, $action->baseYear());
        $this->assertSame(2030, $action->targetYear());
        $this->assertTrue($action->isManual());
        $this->assertCount(0, $action->translations());
    }

    public function testRegisterWithLocaleStoresTheTranslation(): void
    {
        $action = new MitigationAction(id: MitigationActionId::generate());
        $action->register(
            subjectType: CarbonFootprintReportSubjectType::Property,
            carbonFootprintId: 'footprint-1',
            accountId: new AccountId('account-id'),
            measureId: null,
            name: 'Solar panels',
            description: 'Install solar panels',
            baseYear: 2025,
            targetYear: 2030,
            locale: Locale::English,
        );

        $this->assertInstanceOf(MitigationActionTranslated::class, $this->spySubscriber->lastEvent());
        $this->assertCount(1, $action->translations());
        $this->assertSame('Solar panels', $action->defaultName());
    }

    public function testUpdateWithLocaleKeepsTheDefaultTexts(): void
    {
        $action = MitigationActionMother::manual(name: 'Paneles solares');

        $action->update('Solar panels', 'Install them', 2031, Locale::English);

        $this->assertSame('Paneles solares', $action->defaultName());
        $this->assertSame('action-description', $action->defaultDescription());
        $this->assertSame(2031, $action->targetYear());

        FakeLocaleSetter::set(Locale::English);

        $this->assertSame('Solar panels', $action->name());
        $this->assertSame('Install them', $action->description());
    }

    public function testUpdateWithoutLocaleChangesTheDefaultTexts(): void
    {
        $action = MitigationActionMother::manual(name: 'Paneles solares');

        $action->update('Placas solares', 'Instalarlas', 2032);

        $this->assertSame('Placas solares', $action->defaultName());
        $this->assertSame('Instalarlas', $action->defaultDescription());
        $this->assertSame(2032, $action->targetYear());
    }

    public function testNameReturnsTheTranslationOfTheActiveLocaleOrTheDefault(): void
    {
        $action = MitigationActionMother::withTranslation(Locale::English, 'translated-name');

        $this->assertSame('action-name', $action->name());

        FakeLocaleSetter::set(Locale::English);
        $this->assertSame('translated-name', $action->name());

        FakeLocaleSetter::set(Locale::Italian);
        $this->assertSame('action-name', $action->name());
    }

    public function testRegisterThrowsWhenTargetYearIsNotAfterBaseYear(): void
    {
        $this->expectException(MitigationActionInvalidYearsException::class);

        MitigationActionMother::manual(baseYear: 2025, targetYear: 2025);
    }

    public function testUpdateThrowsWhenTargetYearIsNotAfterBaseYear(): void
    {
        $action = MitigationActionMother::manual(baseYear: 2025, targetYear: 2030);

        $this->expectException(MitigationActionInvalidYearsException::class);

        $action->update('name', 'description', 2024);
    }

    public function testSetEffectAddsAndUpdatesIt(): void
    {
        $action = MitigationActionMother::manual();

        $action->setEffect(Source::FixedFacilities, 'gas', 'Gas natural', 0.2);

        $this->assertInstanceOf(MitigationActionEffectAdded::class, $this->spySubscriber->lastEvent());
        $this->assertCount(1, $action->effects());
        $effect = $action->effects()->forSourceAndItem(Source::FixedFacilities, 'gas');
        $this->assertNotNull($effect);
        $this->assertSame(0.2, $effect->reduction());
        $this->assertSame(Scope::Scope1, $effect->scope());

        $action->setEffect(Source::FixedFacilities, 'gas', 'Gas natural', 0.3);

        $this->assertInstanceOf(MitigationActionEffectUpdated::class, $this->spySubscriber->lastEvent());
        $this->assertCount(1, $action->effects());
        $this->assertSame(0.3, $action->effects()->forSourceAndItem(Source::FixedFacilities, 'gas')?->reduction());
    }

    public function testSetEffectDoesNotPublishWhenNothingChanges(): void
    {
        $action = MitigationActionMother::manual();
        $action->setEffect(Source::FixedFacilities, 'gas', 'Gas natural', 0.2);
        $eventsBefore = count($this->spySubscriber->events());

        $action->setEffect(Source::FixedFacilities, 'gas', 'Gas natural', 0.2);

        $this->assertCount($eventsBefore, $this->spySubscriber->events());
    }

    public function testSetEffectThrowsWhenReductionIsOutOfRange(): void
    {
        $action = MitigationActionMother::manual();

        $this->expectException(MitigationActionReductionOutOfRangeException::class);

        $action->setEffect(Source::FixedFacilities, 'gas', 'Gas natural', 0.05, 0.1, 0.3);
    }

    public function testSetEffectThrowsWhenReductionIsAboveOne(): void
    {
        $action = MitigationActionMother::manual();

        $this->expectException(MitigationActionReductionOutOfRangeException::class);

        $action->setEffect(Source::FixedFacilities, 'gas', 'Gas natural', 1.5);
    }

    public function testSetEffectThrowsWhenSourceIsNotSupported(): void
    {
        $action = MitigationActionMother::manual();

        $this->expectException(MitigationActionSourceNotSupportedException::class);

        $action->setEffect(Source::Materials, 'material', 'Material', 0.2);
    }

    public function testSetEffectAcceptsAScope3Source(): void
    {
        $action = MitigationActionMother::manual();

        $action->setEffect(Source::Investments, 'sector', 'Sector', 0.2);

        $this->assertNotNull($action->effects()->forSourceAndItem(Source::Investments, 'sector'));
    }

    public function testSyncEffectsAddsUpdatesAndRemoves(): void
    {
        $action = MitigationActionMother::manual();
        $action->setEffect(Source::FixedFacilities, 'gas', 'Gas natural', 0.2);
        $action->setEffect(Source::RefrigerantLeaks, 'r410', 'R410A', 0.1);

        $action->syncEffects([
            ['source' => Source::FixedFacilities, 'itemId' => 'gas', 'itemName' => 'Gas natural', 'reduction' => 0.25],
            ['source' => Source::BuildingElectricity, 'itemId' => 'marketer', 'itemName' => 'Marketer', 'reduction' => 0.5],
        ]);

        $this->assertCount(2, $action->effects());
        $this->assertSame(0.25, $action->effects()->forSourceAndItem(Source::FixedFacilities, 'gas')?->reduction());
        $this->assertTrue($action->effects()->hasForSourceAndItem(Source::BuildingElectricity, 'marketer'));
        $this->assertFalse($action->effects()->hasForSourceAndItem(Source::RefrigerantLeaks, 'r410'));
        $this->assertInstanceOf(MitigationActionEffectRemoved::class, $this->spySubscriber->lastEvent());
    }

    public function testSyncEffectsThrowsOnDuplicates(): void
    {
        $action = MitigationActionMother::manual();

        $this->expectException(\InvalidArgumentException::class);

        $action->syncEffects([
            ['source' => Source::FixedFacilities, 'itemId' => 'gas', 'itemName' => 'Gas natural', 'reduction' => 0.25],
            ['source' => Source::FixedFacilities, 'itemId' => 'gas', 'itemName' => 'Gas natural', 'reduction' => 0.3],
        ]);
    }

    public function testChangeReductionKeepsTheRange(): void
    {
        $action = MitigationActionMother::manual();
        $action->setEffect(Source::FixedFacilities, 'gas', 'Gas natural', 0.1, 0.1, 0.4);

        $action->changeReduction(Source::FixedFacilities, 'gas', 0.3);

        $effect = $action->effects()->forSourceAndItem(Source::FixedFacilities, 'gas');
        $this->assertNotNull($effect);
        $this->assertSame(0.3, $effect->reduction());
        $this->assertSame(0.1, $effect->minimumReduction());
        $this->assertSame(0.4, $effect->maximumReduction());
    }

    public function testChangeReductionOutOfTheRangeThrows(): void
    {
        $action = MitigationActionMother::manual();
        $action->setEffect(Source::FixedFacilities, 'gas', 'Gas natural', 0.1, 0.1, 0.4);

        $this->expectException(MitigationActionReductionOutOfRangeException::class);

        $action->changeReduction(Source::FixedFacilities, 'gas', 0.5);
    }

    public function testChangeReductionIgnoresMissingEffects(): void
    {
        $action = MitigationActionMother::manual();

        $action->changeReduction(Source::FixedFacilities, 'gas', 0.5);

        $this->assertCount(0, $action->effects());
    }

    public function testRemove(): void
    {
        $action = MitigationActionMother::manual();

        $action->remove();

        $this->assertTrue($action->isRemoved());
        $this->assertNotNull($action->removedAt());
        $this->assertInstanceOf(MitigationActionRemoved::class, $this->spySubscriber->lastEvent());
    }
}
