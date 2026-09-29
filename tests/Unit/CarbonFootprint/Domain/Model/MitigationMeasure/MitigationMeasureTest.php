<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationMeasure;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureEffectAdded;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureEffectRemoved;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureEffectUpdated;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureRemoved;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class MitigationMeasureTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testTranslateReturnsTheTranslatedTexts(): void
    {
        $measure = MitigationMeasureMother::fromName('Paneles solares');
        $measure->translate('Solar panels', 'Install them', Locale::English);

        $this->assertSame('Paneles solares', $measure->name());

        FakeLocaleSetter::set(Locale::English);

        $this->assertSame('Solar panels', $measure->name());
        $this->assertSame('Install them', $measure->description());
        $this->assertSame('Paneles solares', $measure->defaultName());
        $this->assertSame('measure-description', $measure->defaultDescription());
    }

    public function testUpdateWithLocaleKeepsTheDefaultTexts(): void
    {
        $measure = MitigationMeasureMother::fromName('Paneles solares');
        $categoryId = MitigationMeasureCategoryId::generate();

        $measure->update('Solar panels', 'Install them', $categoryId, Locale::English);

        $this->assertSame('Paneles solares', $measure->defaultName());
        $this->assertTrue($categoryId->equals($measure->categoryId() ?? MitigationMeasureCategoryId::generate()));
        $this->assertCount(1, $measure->translations());
    }

    public function testSetEffectAddsUpdatesAndIgnoresUnchanged(): void
    {
        $measure = MitigationMeasureMother::registeredMitigationMeasure();

        $measure->setEffect(Source::FixedFacilities, 0.1, 0.3);
        $this->assertInstanceOf(MitigationMeasureEffectAdded::class, $this->spySubscriber->lastEvent());

        $measure->setEffect(Source::FixedFacilities, 0.2, 0.3);
        $this->assertInstanceOf(MitigationMeasureEffectUpdated::class, $this->spySubscriber->lastEvent());

        $events = count($this->spySubscriber->events());
        $measure->setEffect(Source::FixedFacilities, 0.2, 0.3);
        $this->assertCount($events, $this->spySubscriber->events());

        $this->assertCount(1, $measure->effects());
        $this->assertSame(0.2, $measure->effects()->forSource(Source::FixedFacilities)?->minimumReduction());
    }

    public function testSetEffectRejectsInvalidRange(): void
    {
        $measure = MitigationMeasureMother::registeredMitigationMeasure();

        $this->expectException(\InvalidArgumentException::class);

        $measure->setEffect(Source::FixedFacilities, 0.4, 0.3);
    }

    public function testSetEffectRejectsUnsupportedSource(): void
    {
        $measure = MitigationMeasureMother::registeredMitigationMeasure();

        $this->expectException(\InvalidArgumentException::class);

        $measure->setEffect(Source::Materials, 0.1, 0.3);
    }

    public function testSyncEffectsRemovesTheMissingOnes(): void
    {
        $measure = MitigationMeasureMother::withEffect(Source::FixedFacilities, 0.1, 0.3);

        $measure->syncEffects([
            ['source' => Source::BuildingElectricity, 'minimumReduction' => 0.2, 'maximumReduction' => 0.5],
        ]);

        $this->assertCount(1, $measure->effects());
        $this->assertTrue($measure->effects()->hasForSource(Source::BuildingElectricity));
        $this->assertInstanceOf(MitigationMeasureEffectRemoved::class, $this->spySubscriber->lastEvent());
    }

    public function testSyncEffectsRejectsDuplicates(): void
    {
        $measure = MitigationMeasureMother::registeredMitigationMeasure();

        $this->expectException(\InvalidArgumentException::class);

        $measure->syncEffects([
            ['source' => Source::FixedFacilities, 'minimumReduction' => 0.1, 'maximumReduction' => 0.3],
            ['source' => Source::FixedFacilities, 'minimumReduction' => 0.1, 'maximumReduction' => 0.4],
        ]);
    }

    public function testRemove(): void
    {
        $measure = MitigationMeasureMother::registeredMitigationMeasure();

        $measure->remove();

        $this->assertTrue($measure->isRemoved());
        $this->assertInstanceOf(MitigationMeasureRemoved::class, $this->spySubscriber->lastEvent());
    }
}
