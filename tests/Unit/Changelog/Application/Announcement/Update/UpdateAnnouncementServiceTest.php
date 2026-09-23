<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\Announcement\Update;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Application\Announcement\Update\UpdateAnnouncementRequest;
use App\Changelog\Application\Announcement\Update\UpdateAnnouncementService;
use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Changelog\Domain\Model\Announcement\AnnouncementNotFoundException;
use App\Changelog\Domain\Model\Announcement\AnnouncementStatus;
use App\Changelog\Domain\Model\Announcement\AnnouncementTranslated;
use App\Changelog\Domain\Model\Announcement\AnnouncementUpdated;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Changelog\Infrastructure\Persistence\InMemory\Repository\InMemoryAnnouncementRepository;
use App\Changelog\Infrastructure\Storage\InMemoryAnnouncementManualStorage;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementMother;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateAnnouncementServiceTest extends TestCase
{
    private InMemoryAnnouncementRepository $repository;

    private InMemoryAnnouncementManualStorage $storage;

    private SpyDomainEventSubscriber $spy;

    private UpdateAnnouncementService $sut;

    protected function setUp(): void
    {
        FakeLocaleSetter::reset();
        DomainEventPublisher::instance()->reset();
        $this->repository = AnnouncementRepositoryMother::inMemory();
        $this->storage = new InMemoryAnnouncementManualStorage();
        $this->sut = new UpdateAnnouncementService($this->repository, $this->storage, new AnnouncementAuthorizer());
    }

    protected function tearDown(): void
    {
        FakeLocaleSetter::reset();
    }

    public function testUpdatesTheBaseFieldsInTheDefaultLocale(): void
    {
        $announcement = $this->given(AnnouncementMother::draft('Título'));

        ($this->sut)($this->request($announcement, title: 'Nuevo título', category: ChangelogCategory::Security, date: '2026-01-15'), UserMother::adminUser());

        $this->assertSame('Nuevo título', $announcement->title());
        $this->assertSame(ChangelogCategory::Security, $announcement->category());
        $this->assertSame('2026-01-15', $announcement->date()->format('Y-m-d'));
        $this->assertCount(0, $announcement->translations());
        $this->assertCount(1, $this->eventsOf(AnnouncementUpdated::class));
    }

    public function testEditingInAnotherLocaleTranslatesAndKeepsTheBaseFields(): void
    {
        $announcement = $this->given(AnnouncementMother::draft('Título'));

        FakeLocaleSetter::set(Locale::English);

        ($this->sut)($this->request($announcement, title: 'Title'), UserMother::adminUser());

        $this->assertSame('Title', $announcement->title(Locale::English));
        $this->assertSame('Título', $announcement->title(Locale::Spanish));
        $this->assertCount(1, $announcement->translations());
        $this->assertCount(1, $this->eventsOf(AnnouncementTranslated::class));
    }

    public function testRemoveManualKeepsTheText(): void
    {
        $announcement = $this->given(AnnouncementMother::withManual(Locale::Spanish));
        $manualPath = (string) $announcement->manualPath();
        $this->storage->add($manualPath, '%PDF-1.4');

        ($this->sut)($this->request($announcement, title: 'Con manual', removeManual: true), UserMother::adminUser());

        $this->assertFalse($announcement->hasManual());
        $this->assertNull($announcement->manualFileName());
        $this->assertSame('Con manual', $announcement->title());
        $this->assertFalse($this->storage->exists($manualPath));
    }

    public function testANewManualReplacesThePreviousOne(): void
    {
        $announcement = $this->given(AnnouncementMother::draft('Título'));
        $announcement->update($announcement->category(), $announcement->date(), null, 'Título', 'Resumen', '', null, 'antiguo.pdf', 'old/es.pdf');
        $this->storage->add('old/es.pdf', '%PDF-1.4 old');

        $localPath = (string) tempnam(sys_get_temp_dir(), 'manual');
        file_put_contents($localPath, '%PDF-1.4 new');

        ($this->sut)($this->request($announcement, manualLocalPath: $localPath, manualFileName: 'nuevo.pdf'), UserMother::adminUser());

        @unlink($localPath);

        $this->assertSame('nuevo.pdf', $announcement->manualFileName());
        $this->assertSame(sprintf('%s/es.pdf', (string) $announcement->id()), $announcement->manualPath());
        $this->assertFalse($this->storage->exists('old/es.pdf'));
        $this->assertTrue($this->storage->exists((string) $announcement->manualPath()));
    }

    public function testANewTranslationDoesNotInheritTheBaseManual(): void
    {
        $announcement = $this->given(AnnouncementMother::withManual(Locale::Spanish));
        $manualPath = (string) $announcement->manualPath();
        $this->storage->add($manualPath, '%PDF-1.4');

        FakeLocaleSetter::set(Locale::English);

        ($this->sut)($this->request($announcement, title: 'Title'), UserMother::adminUser());

        $this->assertFalse($announcement->hasManual(Locale::English));
        $this->assertTrue($announcement->hasManual(Locale::Spanish));
        $this->assertTrue($this->storage->exists($manualPath));
    }

    public function testAManualSharedWithAnotherLocaleIsNotDeleted(): void
    {
        $announcement = $this->given(AnnouncementMother::draft('Título'));
        $announcement->update($announcement->category(), $announcement->date(), null, 'Título', 'Resumen', '', null, 'manual.pdf', 'shared/en.pdf');
        $announcement->translate('Title', 'Summary', '', null, 'manual.pdf', 'shared/en.pdf', Locale::English);
        $this->storage->add('shared/en.pdf', '%PDF-1.4');

        ($this->sut)($this->request($announcement, removeManual: true), UserMother::adminUser());

        $this->assertFalse($announcement->hasManual(Locale::Spanish));
        $this->assertTrue($announcement->hasManual(Locale::English));
        $this->assertTrue($this->storage->exists('shared/en.pdf'));
    }

    public function testEditingAPublishedAnnouncementKeepsItsStatus(): void
    {
        $announcement = $this->given(AnnouncementMother::published('Título'));

        ($this->sut)($this->request($announcement, title: 'Otro título'), UserMother::adminUser());

        $this->assertSame(AnnouncementStatus::Published, $announcement->status());
    }

    public function testUnknownAnnouncement(): void
    {
        $this->expectException(AnnouncementNotFoundException::class);

        ($this->sut)(new UpdateAnnouncementRequest(AnnouncementId::generate(), title: 'Título', summary: 'Resumen'), UserMother::adminUser());
    }

    public function testUsersCannotEdit(): void
    {
        $announcement = $this->given(AnnouncementMother::draft('Título'));

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)($this->request($announcement), UserMother::fromUsernameAndRole('user@dediez.software', Role::User));
    }

    private function given(Announcement $announcement): Announcement
    {
        $this->repository->add($announcement);
        $this->spy = new SpyDomainEventSubscriber();

        return $announcement;
    }

    private function request(
        Announcement $announcement,
        ?string $title = null,
        ?ChangelogCategory $category = null,
        ?string $date = null,
        ?string $manualLocalPath = null,
        ?string $manualFileName = null,
        bool $removeManual = false,
    ): UpdateAnnouncementRequest {
        return new UpdateAnnouncementRequest(
            announcementId: $announcement->id(),
            category: $category ?? $announcement->category(),
            date: null !== $date ? new \DateTimeImmutable($date) : $announcement->date(),
            version: $announcement->version(),
            title: $title ?? $announcement->title(),
            summary: 'Resumen',
            manualLocalPath: $manualLocalPath,
            manualFileName: $manualFileName,
            removeManual: $removeManual,
        );
    }

    /**
     * @param class-string $class
     *
     * @return list<object>
     */
    private function eventsOf(string $class): array
    {
        return array_values(array_filter($this->spy->events(), static fn($event): bool => $event instanceof $class));
    }
}
