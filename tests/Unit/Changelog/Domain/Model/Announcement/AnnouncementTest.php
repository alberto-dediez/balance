<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Domain\Model\Announcement;

use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Changelog\Domain\Model\Announcement\AnnouncementPublished;
use App\Changelog\Domain\Model\Announcement\AnnouncementRegistered;
use App\Changelog\Domain\Model\Announcement\AnnouncementRemoved;
use App\Changelog\Domain\Model\Announcement\AnnouncementStatus;
use App\Changelog\Domain\Model\Announcement\AnnouncementTranslated;
use App\Changelog\Domain\Model\Announcement\AnnouncementUnpublished;
use App\Changelog\Domain\Model\Announcement\AnnouncementUpdated;
use App\Changelog\Domain\Model\Announcement\YouTubeVideo;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class AnnouncementTest extends TestCase
{
    private SpyDomainEventSubscriber $spy;

    protected function setUp(): void
    {
        FakeLocaleSetter::reset();
        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();
    }

    protected function tearDown(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testRegisterSetsTheBaseFieldsWithoutTranslations(): void
    {
        $sut = new Announcement(AnnouncementId::generate(), registeredAt: new \DateTimeImmutable('2000-01-01'));

        $sut->register(ChangelogCategory::Fix, new \DateTimeImmutable('2026-09-22'), '1.2.0', 'Título', 'Resumen', 'Contenido', null, null, null, null);

        $this->assertInstanceOf(AnnouncementRegistered::class, $this->spy->lastEvent());
        $this->assertSame(ChangelogCategory::Fix, $sut->category());
        $this->assertSame('2026-09-22', $sut->date()->format('Y-m-d'));
        $this->assertSame('1.2.0', $sut->version());
        $this->assertSame('Título', $sut->title());
        $this->assertSame('Resumen', $sut->summary());
        $this->assertSame('Contenido', $sut->body());
        $this->assertCount(0, $sut->translations());
        $this->assertNotSame('2000-01-01', $sut->registeredAt()->format('Y-m-d'));
        $this->assertSame(AnnouncementStatus::Draft, $sut->status());
    }

    public function testRegisterInAnotherLocaleAlsoTranslates(): void
    {
        $sut = new Announcement(AnnouncementId::generate());

        $sut->register(ChangelogCategory::Feature, new \DateTimeImmutable('today'), null, 'Title', 'Summary', 'Body', null, null, null, null, Locale::English);

        $this->assertInstanceOf(AnnouncementTranslated::class, $this->spy->lastEvent());
        $this->assertCount(1, $sut->translations());
        $this->assertSame('Title', $sut->title(Locale::English));
    }

    public function testTranslateAddsANewLocaleAndUpdatesAnExistingOneWithoutDuplicating(): void
    {
        $sut = AnnouncementMother::draft('Título');

        $sut->translate('Title', 'Summary', 'Body', null, null, null, Locale::English);

        $this->assertCount(1, $sut->translations());

        $sut->translate('New title', 'Summary', 'Body', null, null, null, Locale::English);

        $this->assertCount(1, $sut->translations());
        $this->assertSame('New title', $sut->title(Locale::English));
        $this->assertSame('Título', $sut->title(Locale::Spanish));
    }

    public function testGettersResolveTheCurrentLocaleAndFallBackToTheBaseFields(): void
    {
        $sut = AnnouncementMother::withTranslations(Locale::Spanish, Locale::English);

        FakeLocaleSetter::set(Locale::English);

        $this->assertSame('Título en', $sut->title());
        $this->assertSame('Resumen en', $sut->summary());
        $this->assertSame('Contenido en', $sut->body());

        FakeLocaleSetter::set(Locale::French);

        $this->assertSame('Título es', $sut->title());
        $this->assertSame('Título es', (string) $sut);
    }

    public function testVideoAndManualAreResolvedPerLocale(): void
    {
        $sut = AnnouncementMother::withManual(Locale::English);
        $sut->translate('Novedad con manual', 'Resumen', 'Contenido', YouTubeVideo::fromId('dQw4w9WgXcQ'), 'manual.pdf', 'x/en.pdf', Locale::English);

        $this->assertTrue($sut->hasManual(Locale::English));
        $this->assertTrue($sut->hasVideo(Locale::English));
        $this->assertFalse($sut->hasManual(Locale::Spanish));
        $this->assertFalse($sut->hasVideo(Locale::Spanish));
    }

    public function testUpdateInTheDefaultLocaleChangesTheBaseFields(): void
    {
        $sut = AnnouncementMother::draft('Título');

        $sut->update(ChangelogCategory::Security, new \DateTimeImmutable('2026-01-15'), '2.0', 'Nuevo', 'Resumen', 'Contenido', null, null, null);

        $this->assertInstanceOf(AnnouncementUpdated::class, $this->spy->lastEvent());
        $this->assertSame(ChangelogCategory::Security, $sut->category());
        $this->assertSame('Nuevo', $sut->title());
        $this->assertCount(0, $sut->translations());
        $this->assertInstanceOf(\DateTimeImmutable::class, $sut->updatedAt());
    }

    public function testUpdateInAnotherLocaleKeepsTheBaseFieldsAndTranslates(): void
    {
        $sut = AnnouncementMother::draft('Título');

        $sut->update(ChangelogCategory::Fix, new \DateTimeImmutable('2026-01-15'), null, 'Title', 'Summary', 'Body', null, null, null, Locale::English);

        $this->assertInstanceOf(AnnouncementTranslated::class, $this->spy->lastEvent());
        $this->assertSame(ChangelogCategory::Fix, $sut->category());
        $this->assertSame('Título', $sut->title(Locale::Spanish));
        $this->assertSame('Title', $sut->title(Locale::English));
    }

    public function testScheduleSetsTheScheduleAndTheNotification(): void
    {
        $sut = AnnouncementMother::draft();

        $sut->schedule(new \DateTimeImmutable('2030-01-01 10:00'), true);

        $this->assertSame('2030-01-01 10:00', $sut->scheduledAt()?->format('Y-m-d H:i'));
        $this->assertTrue($sut->notifyOnPublish());
        $this->assertSame(AnnouncementStatus::Scheduled, $sut->status());
    }

    public function testScheduleTwiceReschedules(): void
    {
        $sut = AnnouncementMother::scheduledFor('2030-01-01 10:00', true);

        $sut->schedule(new \DateTimeImmutable('2031-01-01 10:00'), false);

        $this->assertSame('2031-01-01 10:00', $sut->scheduledAt()?->format('Y-m-d H:i'));
        $this->assertFalse($sut->notifyOnPublish());
    }

    public function testSchedulingAPublishedAnnouncementFails(): void
    {
        $sut = AnnouncementMother::published();

        $this->expectException(\LogicException::class);

        $sut->schedule(new \DateTimeImmutable('2030-01-01 10:00'), false);
    }

    public function testPublishClearsTheSchedule(): void
    {
        $sut = AnnouncementMother::scheduledFor('2030-01-01 10:00', true);

        $sut->publish(true);

        $this->assertInstanceOf(AnnouncementPublished::class, $this->spy->lastEvent());
        $this->assertInstanceOf(\DateTimeImmutable::class, $sut->publishedAt());
        $this->assertNull($sut->scheduledAt());
        $this->assertFalse($sut->notifyOnPublish());
        $this->assertSame(AnnouncementStatus::Published, $sut->status());
    }

    public function testPublishTwiceRecordsASingleEvent(): void
    {
        $sut = AnnouncementMother::draft();

        $sut->publish(false);
        $sut->publish(false);

        $published = array_filter($this->spy->events(), static fn($event): bool => $event instanceof AnnouncementPublished);

        $this->assertCount(1, $published);
    }

    public function testUnpublishAScheduledAnnouncementCancelsTheSchedule(): void
    {
        $sut = AnnouncementMother::scheduledFor('2030-01-01 10:00', true);

        $sut->unpublish();

        $this->assertInstanceOf(AnnouncementUnpublished::class, $this->spy->lastEvent());
        $this->assertNull($sut->scheduledAt());
        $this->assertFalse($sut->notifyOnPublish());
        $this->assertSame(AnnouncementStatus::Draft, $sut->status());
    }

    public function testUnpublishADraftRecordsNothing(): void
    {
        $sut = AnnouncementMother::draft();
        $eventsBefore = count($this->spy->events());

        $sut->unpublish();

        $this->assertCount($eventsBefore, $this->spy->events());
    }

    public function testRemove(): void
    {
        $sut = AnnouncementMother::published();

        $sut->remove();

        $this->assertInstanceOf(AnnouncementRemoved::class, $this->spy->lastEvent());
        $this->assertTrue($sut->isRemoved());
        $this->assertInstanceOf(\DateTimeImmutable::class, $sut->removedAt());
    }
}
