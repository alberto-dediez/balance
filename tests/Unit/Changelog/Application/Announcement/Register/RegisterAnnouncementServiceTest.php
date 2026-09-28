<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\Announcement\Register;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Application\Announcement\Register\RegisterAnnouncementRequest;
use App\Changelog\Application\Announcement\Register\RegisterAnnouncementService;
use App\Changelog\Application\Announcement\Update\UpdateAnnouncementRequest;
use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Changelog\Domain\Model\Announcement\AnnouncementRegistered;
use App\Changelog\Domain\Model\Announcement\AnnouncementTranslated;
use App\Changelog\Domain\Model\Announcement\InvalidYouTubeUrlException;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Changelog\Infrastructure\Persistence\InMemory\Repository\InMemoryAnnouncementRepository;
use App\Library\Domain\Model\Media\MediaId;
use App\Security\Application\UnauthorizedActionException;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RegisterAnnouncementServiceTest extends TestCase
{
    private InMemoryAnnouncementRepository $repository;

    private SpyDomainEventSubscriber $spy;

    private RegisterAnnouncementService $sut;

    protected function setUp(): void
    {
        FakeLocaleSetter::reset();
        DomainEventPublisher::instance()->reset();
        $this->spy = new SpyDomainEventSubscriber();
        $this->repository = AnnouncementRepositoryMother::inMemory();
        $this->sut = new RegisterAnnouncementService($this->repository, new AnnouncementAuthorizer());
    }

    protected function tearDown(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testRegistersInTheDefaultLocaleWithoutTranslations(): void
    {
        $response = ($this->sut)(new RegisterAnnouncementRequest(
            category: ChangelogCategory::Improvement,
            date: new \DateTimeImmutable('2026-09-22'),
            version: '2.0',
            title: 'Título',
            summary: 'Resumen',
            body: 'Contenido',
        ), UserMother::adminUser());

        $announcement = $this->repository->find($response->announcementId);

        $this->assertInstanceOf(Announcement::class, $announcement);
        $this->assertSame('Título', $response->title);
        $this->assertSame(ChangelogCategory::Improvement, $announcement->category());
        $this->assertCount(0, $announcement->translations());
        $this->assertFalse($announcement->isPublished());
        $this->assertInstanceOf(AnnouncementRegistered::class, $this->spy->lastEvent());
    }

    public function testRegistersInAnotherLocaleAlsoTranslating(): void
    {
        FakeLocaleSetter::set(Locale::English);

        $response = ($this->sut)(new RegisterAnnouncementRequest(title: 'Title', summary: 'Summary'), UserMother::adminUser());

        $announcement = $this->repository->find($response->announcementId);

        $this->assertInstanceOf(Announcement::class, $announcement);
        $this->assertCount(1, $announcement->translations());
        $this->assertSame('Title', $announcement->title(Locale::English));
        $this->assertInstanceOf(AnnouncementTranslated::class, $this->spy->lastEvent());
    }

    public function testRegistersTheManualForTheCurrentLocale(): void
    {
        $manualId = MediaId::generate();

        $response = ($this->sut)(new RegisterAnnouncementRequest(title: 'Título', summary: 'Resumen', manualId: $manualId), UserMother::adminUser());

        $announcement = $this->repository->find($response->announcementId);

        $this->assertInstanceOf(Announcement::class, $announcement);
        $this->assertEquals($manualId, $announcement->manualId());
        $this->assertTrue($announcement->hasManual());
    }

    public function testAManualRegisteredInAnotherLocaleBelongsToItsTranslation(): void
    {
        FakeLocaleSetter::set(Locale::English);
        $manualId = MediaId::generate();

        $response = ($this->sut)(new RegisterAnnouncementRequest(title: 'Title', summary: 'Summary', manualId: $manualId), UserMother::adminUser());

        $announcement = $this->repository->find($response->announcementId);

        $this->assertInstanceOf(Announcement::class, $announcement);
        $this->assertEquals($manualId, $announcement->manualId(Locale::English));
        $this->assertCount(1, $announcement->translations());
    }

    public function testExtractsTheYouTubeVideo(): void
    {
        $response = ($this->sut)(new RegisterAnnouncementRequest(title: 'Título', summary: 'Resumen', youtubeUrl: 'https://youtu.be/dQw4w9WgXcQ'), UserMother::adminUser());

        $this->assertSame('dQw4w9WgXcQ', $this->repository->find($response->announcementId)?->youtubeVideo()?->id);
    }

    public function testRejectsAnInvalidYouTubeUrl(): void
    {
        $this->expectException(InvalidYouTubeUrlException::class);

        ($this->sut)(new RegisterAnnouncementRequest(title: 'Título', summary: 'Resumen', youtubeUrl: 'https://vimeo.com/123'), UserMother::adminUser());
    }

    public function testOwnersCannotRegister(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new RegisterAnnouncementRequest(title: 'Título', summary: 'Resumen'), UserMother::ownerUser());
    }

    public function testIsHandled(): void
    {
        $this->assertTrue($this->sut->isHandled(new RegisterAnnouncementRequest()));
        $this->assertFalse($this->sut->isHandled(new UpdateAnnouncementRequest(AnnouncementId::generate())));
    }
}
