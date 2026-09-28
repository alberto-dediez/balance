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
use App\Library\Domain\Model\Media\MediaId;
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

    private SpyDomainEventSubscriber $spy;

    private UpdateAnnouncementService $sut;

    protected function setUp(): void
    {
        FakeLocaleSetter::reset();
        DomainEventPublisher::instance()->reset();
        $this->repository = AnnouncementRepositoryMother::inMemory();
        $this->sut = new UpdateAnnouncementService($this->repository, new AnnouncementAuthorizer());
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

        ($this->sut)($this->request($announcement, title: 'Con manual'), UserMother::adminUser());

        $this->assertFalse($announcement->hasManual());
        $this->assertNull($announcement->manualId());
        $this->assertSame('Con manual', $announcement->title());
        $this->assertSame('Resumen', $announcement->summary());
    }

    public function testAnEmptyManualRemovesItForTheCurrentLocale(): void
    {
        $englishManual = MediaId::generate();
        $announcement = $this->given(AnnouncementMother::withManual(Locale::Spanish));
        $announcement->translate('Title', 'Summary', '', null, $englishManual, Locale::English);

        ($this->sut)($this->request($announcement), UserMother::adminUser());

        $this->assertFalse($announcement->hasManual(Locale::Spanish));
        $this->assertEquals($englishManual, $announcement->manualId(Locale::English));
    }

    public function testANewManualReplacesThePreviousOne(): void
    {
        $newManual = MediaId::generate();
        $announcement = $this->given(AnnouncementMother::withManual(Locale::Spanish));

        ($this->sut)($this->request($announcement, manualId: $newManual), UserMother::adminUser());

        $this->assertEquals($newManual, $announcement->manualId());
    }

    public function testANewTranslationDoesNotInheritTheBaseManual(): void
    {
        $announcement = $this->given(AnnouncementMother::withManual(Locale::Spanish));

        FakeLocaleSetter::set(Locale::English);

        ($this->sut)($this->request($announcement, title: 'Title'), UserMother::adminUser());

        $this->assertFalse($announcement->hasManual(Locale::English));
        $this->assertTrue($announcement->hasManual(Locale::Spanish));
    }

    public function testChangingTheTranslationManualKeepsTheBaseOne(): void
    {
        $englishManual = MediaId::generate();
        $announcement = $this->given(AnnouncementMother::withManual(Locale::Spanish));
        $baseManual = $announcement->manualId(Locale::Spanish);

        FakeLocaleSetter::set(Locale::English);

        ($this->sut)($this->request($announcement, title: 'Title', manualId: $englishManual), UserMother::adminUser());

        $this->assertEquals($englishManual, $announcement->manualId(Locale::English));
        $this->assertEquals($baseManual, $announcement->manualId(Locale::Spanish));
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
        ?MediaId $manualId = null,
    ): UpdateAnnouncementRequest {
        return new UpdateAnnouncementRequest(
            announcementId: $announcement->id(),
            category: $category ?? $announcement->category(),
            date: null !== $date ? new \DateTimeImmutable($date) : $announcement->date(),
            version: $announcement->version(),
            title: $title ?? $announcement->title(),
            summary: 'Resumen',
            manualId: $manualId,
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
