<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\PublicLanding;

use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingBlockAdded;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingBlockRemoved;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingOwnerType;
use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingRegistered;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Library\Domain\Model\Media\MediaId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use PHPUnit\Framework\TestCase;

class PublicLandingTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        LocaleGetter::setLocale(null);
    }

    protected function tearDown(): void
    {
        LocaleGetter::setLocale(null);
    }

    public function testRegistersTheLandingOfAnOwner(): void
    {
        $eventId = (string) EventId::generate();

        $publicLanding = PublicLandingMother::forEvent($eventId);

        $this->assertSame(PublicLandingOwnerType::Event, $publicLanding->ownerType());
        $this->assertSame($eventId, $publicLanding->ownerId());
        $this->assertFalse($publicLanding->isActive());
        $this->assertFalse($publicLanding->hasContent());
        $registered = array_filter(
            $publicLanding->recordedEvents(),
            static fn($event) => $event instanceof PublicLandingRegistered,
        );
        $this->assertCount(1, $registered);
    }

    public function testUpdatesItsContent(): void
    {
        $publicLanding = PublicLandingMother::registeredPublicLanding();
        $heroImage = MediaId::generate();

        $publicLanding->update(
            active: true,
            title: 'Bienvenido',
            subtitle: 'Calcula tu huella',
            intro: 'Medimos el impacto de tu viaje.',
            heroImage: $heroImage,
            ctaLabel: 'Empezar',
        );

        $this->assertTrue($publicLanding->isActive());
        $this->assertSame('Bienvenido', $publicLanding->title());
        $this->assertSame('Calcula tu huella', $publicLanding->subtitle());
        $this->assertSame('Medimos el impacto de tu viaje.', $publicLanding->intro());
        $this->assertSame('Empezar', $publicLanding->ctaLabel());
        $this->assertSame((string) $heroImage, (string) $publicLanding->heroImage());
        $this->assertTrue($publicLanding->hasContent());
    }

    public function testAddsUpdatesAndRemovesBlocks(): void
    {
        $publicLanding = PublicLandingMother::registeredPublicLanding();

        $publicLanding->setBlock(0, null, 'Primero', 'Texto uno');
        $publicLanding->setBlock(1, null, 'Segundo', 'Texto dos');

        $this->assertCount(2, $publicLanding->blocks());
        $this->assertSame('Primero', $publicLanding->blocks()[0]->title());
        $this->assertSame('Segundo', $publicLanding->blocks()[1]->title());

        $publicLanding->setBlock(1, null, 'Segundo corregido', 'Texto dos');

        $this->assertCount(2, $publicLanding->blocks());
        $this->assertSame('Segundo corregido', $publicLanding->blocks()[1]->title());

        $publicLanding->truncateBlocks(1);

        $this->assertCount(1, $publicLanding->blocks());
        $this->assertSame('Primero', $publicLanding->blocks()[0]->title());
    }

    public function testRecordsOneAddedEventPerBlockAndOneRemovedWhenTruncating(): void
    {
        $publicLanding = PublicLandingMother::registeredPublicLanding();

        $publicLanding->setBlock(0, null, 'Primero', 'Texto uno');
        $publicLanding->setBlock(1, null, 'Segundo', 'Texto dos');
        $publicLanding->truncateBlocks(1);

        $added = array_filter($publicLanding->recordedEvents(), static fn($event) => $event instanceof PublicLandingBlockAdded);
        $removed = array_filter($publicLanding->recordedEvents(), static fn($event) => $event instanceof PublicLandingBlockRemoved);

        $this->assertCount(2, $added);
        $this->assertCount(1, $removed);
    }

    public function testTranslatingLeavesTheDefaultTextsUntouched(): void
    {
        $publicLanding = PublicLandingMother::registeredPublicLanding();

        $publicLanding->update(
            active: true,
            title: 'Bienvenido',
            subtitle: 'Calcula tu huella',
            intro: 'Medimos el impacto de tu viaje.',
            heroImage: null,
            ctaLabel: 'Empezar',
        );

        $publicLanding->update(
            active: true,
            title: 'Welcome',
            subtitle: 'Measure your footprint',
            intro: 'We measure the impact of your trip.',
            heroImage: null,
            ctaLabel: 'Start',
            locale: Locale::English,
        );

        LocaleGetter::setLocale(Locale::English);
        $this->assertSame('Welcome', $publicLanding->title());
        $this->assertSame('Start', $publicLanding->ctaLabel());

        LocaleGetter::setLocale(Locale::Spanish);
        $this->assertSame('Bienvenido', $publicLanding->title());
        $this->assertSame('Empezar', $publicLanding->ctaLabel());
    }

    public function testTranslatingABlockLeavesItsDefaultTextsUntouched(): void
    {
        $publicLanding = PublicLandingMother::registeredPublicLanding();

        $publicLanding->setBlock(0, null, 'Primero', 'Texto uno');
        $publicLanding->setBlock(0, null, 'First', 'Text one', Locale::English);

        LocaleGetter::setLocale(Locale::English);
        $this->assertSame('First', $publicLanding->blocks()[0]->title());
        $this->assertSame('Text one', $publicLanding->blocks()[0]->text());

        LocaleGetter::setLocale(Locale::Spanish);
        $this->assertSame('Primero', $publicLanding->blocks()[0]->title());
        $this->assertSame('Texto uno', $publicLanding->blocks()[0]->text());
    }

    public function testABlockAddedWhileTranslatingKeepsTheTextAsItsDefault(): void
    {
        $publicLanding = PublicLandingMother::registeredPublicLanding();

        $publicLanding->setBlock(0, null, 'First', 'Text one', Locale::English);

        LocaleGetter::setLocale(Locale::Spanish);
        $this->assertSame('First', $publicLanding->blocks()[0]->title());
    }
}
