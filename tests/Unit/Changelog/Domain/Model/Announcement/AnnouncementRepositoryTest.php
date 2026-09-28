<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Domain\Model\Announcement;

use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Changelog\Domain\Model\Announcement\AnnouncementFilter;
use App\Changelog\Domain\Model\Announcement\AnnouncementRepository;
use App\Changelog\Domain\Model\Announcement\AnnouncementStatus;
use App\Changelog\Domain\Model\Announcement\YouTubeVideo;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Library\Domain\Model\Media\MediaId;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class AnnouncementRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        FakeLocaleSetter::reset();
        parent::tearDown();
    }

    public function testAddAnnouncementToRepository(): void
    {
        $repository = $this->createSUT();

        $announcement = AnnouncementMother::registeredAnnouncement();

        $this->assertNull($repository->find($announcement->id()));

        $repository->add($announcement);

        $recoverAnnouncement = $repository->find($announcement->id());

        $this->assertNotNull($recoverAnnouncement);
        $this->assertEquals($announcement->id(), $recoverAnnouncement->id());
    }

    public function testRemoveAnnouncementToRepository(): void
    {
        $repository = $this->createSUT();

        $announcement = AnnouncementMother::registeredAnnouncement();

        $repository->add($announcement);

        $recoverAnnouncement = $repository->find($announcement->id());

        $this->assertNotNull($recoverAnnouncement);
        $this->assertEquals($announcement->id(), $recoverAnnouncement->id());

        $repository->remove($announcement);

        $this->assertNull($repository->find($announcement->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(AnnouncementMother::random());
        $repository->add(AnnouncementMother::random());
        $repository->add(AnnouncementMother::random());
        $repository->add(AnnouncementMother::random());
        $repository->add(AnnouncementMother::random());
        $repository->add(AnnouncementMother::fromTitle('Título específico'));

        $this->assertCount(6, $repository->findByFilter(new AnnouncementFilter()));

        $result = $repository->findByFilter(new AnnouncementFilter(text: 'Título específico'));

        $this->assertCount(1, $result);
    }

    public function testFindByFilterOrdersByDate(): void
    {
        $repository = $this->createSUT();

        $repository->add(AnnouncementMother::withDate('2026-01-02', 'Bravo'));
        $repository->add(AnnouncementMother::withDate('2026-01-03', 'Charlie'));
        $repository->add(AnnouncementMother::withDate('2026-01-01', 'Alfa'));

        $this->assertSame(['Charlie', 'Bravo', 'Alfa'], $this->titlesOf($repository->findByFilter(new AnnouncementFilter())));

        $filter = new AnnouncementFilter(limit: 2);
        $filter->orderWay = 'ASC';

        $this->assertSame(['Alfa', 'Bravo'], $this->titlesOf($repository->findByFilter($filter)));

        $filter->page = 1;

        $this->assertSame(['Charlie'], $this->titlesOf($repository->findByFilter($filter)));
        $this->assertSame(3, $repository->totalResultsByFilter($filter));
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(AnnouncementMother::random());
        $repository->add(AnnouncementMother::random());
        $repository->add(AnnouncementMother::random());
        $repository->add(AnnouncementMother::random());
        $repository->add(AnnouncementMother::random());
        $repository->add(AnnouncementMother::random());
        $repository->add(AnnouncementMother::random());
        $repository->add(AnnouncementMother::random());
        $repository->add(AnnouncementMother::random());
        $repository->add(AnnouncementMother::random());
        $repository->add(AnnouncementMother::fromTitle('Título específico'));

        $this->assertCount(10, $repository->findByFilter(new AnnouncementFilter()));
        $this->assertCount(11, $repository->findByFilter(new AnnouncementFilter(limit: 0)));
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $announcement = AnnouncementMother::registeredAnnouncement();

        $repository->add($announcement);

        $recoverAnnouncement = $repository->find($announcement->id());

        $this->assertNotNull($recoverAnnouncement);

        $recoverAnnouncement->remove();

        $repository->add($recoverAnnouncement);

        $this->assertNull($repository->find($announcement->id()));
        $this->assertSame(0, $repository->totalResultsByFilter(new AnnouncementFilter()));
    }

    public function testFindByFilterWithStatus(): void
    {
        $repository = $this->createSUT();

        $repository->add(AnnouncementMother::draft('Borrador'));
        $repository->add(AnnouncementMother::scheduledFor('2030-01-01 10:00', false, 'Programada'));
        $repository->add(AnnouncementMother::published('Publicada'));

        $this->assertSame(['Borrador'], $this->titlesOf($repository->findByFilter(new AnnouncementFilter(status: AnnouncementStatus::Draft))));
        $this->assertSame(['Programada'], $this->titlesOf($repository->findByFilter(new AnnouncementFilter(status: AnnouncementStatus::Scheduled))));
        $this->assertSame(['Publicada'], $this->titlesOf($repository->findByFilter(new AnnouncementFilter(status: AnnouncementStatus::Published))));
        $this->assertSame(3, $repository->totalResultsByFilter(new AnnouncementFilter()));
    }

    public function testFindByFilterWithCategory(): void
    {
        $repository = $this->createSUT();

        $repository->add(AnnouncementMother::draft('Nueva función', category: ChangelogCategory::Feature));
        $repository->add(AnnouncementMother::draft('Corrección', category: ChangelogCategory::Fix));

        $this->assertSame(['Corrección'], $this->titlesOf($repository->findByFilter(new AnnouncementFilter(category: ChangelogCategory::Fix))));
    }

    public function testFindRecoverWithTranslation(): void
    {
        $repository = $this->createSUT();

        $announcement = AnnouncementMother::withTranslations(Locale::Spanish, Locale::English);
        $repository->add($announcement);

        $found = $repository->find($announcement->id());

        $this->assertInstanceOf(Announcement::class, $found);
        $this->assertCount(1, $found->translations());
        $this->assertSame('Título es', $found->title());

        FakeLocaleSetter::set(Locale::English);

        $this->assertSame('Título en', $found->title());
    }

    public function testAddUpdatesAnExistingTranslation(): void
    {
        $repository = $this->createSUT();

        $announcement = AnnouncementMother::withTranslations(Locale::English);
        $repository->add($announcement);

        $found = $repository->find($announcement->id());
        $this->assertInstanceOf(Announcement::class, $found);

        $manualId = MediaId::generate();
        $found->translate('New title', 'New summary', 'New body', YouTubeVideo::fromId('dQw4w9WgXcQ'), $manualId, Locale::English);
        $repository->add($found);

        $this->clearPersistence();

        $updated = $repository->find($announcement->id());

        $this->assertInstanceOf(Announcement::class, $updated);
        $this->assertCount(1, $updated->translations());
        $this->assertSame('New title', $updated->title(Locale::English));
        $this->assertSame('New summary', $updated->summary(Locale::English));
        $this->assertSame('New body', $updated->body(Locale::English));
        $this->assertSame('dQw4w9WgXcQ', (string) $updated->youtubeVideo(Locale::English));
        $this->assertEquals($manualId, $updated->manualId(Locale::English));
        $this->assertSame('Título es', $updated->title(Locale::Spanish));
    }

    public function testFindByFilterWithTitleTranslated(): void
    {
        $repository = $this->createSUT();

        $announcement = AnnouncementMother::fromTitle('Informes');
        $announcement->translate('Reports', 'Brand NEW dashboards', '', null, null, Locale::English);
        $repository->add($announcement);
        $repository->add(AnnouncementMother::fromTitle('Otra cosa'));

        $this->assertSame(1, $repository->totalResultsByFilter(new AnnouncementFilter(text: 'INFORMES')));
        $this->assertSame(0, $repository->totalResultsByFilter(new AnnouncementFilter(text: 'reports')));

        FakeLocaleSetter::set(Locale::English);

        $this->assertSame(1, $repository->totalResultsByFilter(new AnnouncementFilter(text: 'REPORTS')));
        $this->assertSame(1, $repository->totalResultsByFilter(new AnnouncementFilter(text: 'new dash')));
        $this->assertSame(1, $repository->totalResultsByFilter(new AnnouncementFilter(text: 'otra cosa')));
        $this->assertSame(0, $repository->totalResultsByFilter(new AnnouncementFilter(text: 'informes')));
    }

    public function testFindScheduledToPublishUntilReturnsOnlyDueScheduledAnnouncements(): void
    {
        $repository = $this->createSUT();

        $due = AnnouncementMother::scheduledFor('2026-01-01 10:00', false, 'Vencida');
        $future = AnnouncementMother::scheduledFor('2030-01-01 10:00', false, 'Futura');
        $removed = AnnouncementMother::scheduledFor('2026-01-01 09:00', false, 'Eliminada');
        $removed->remove();

        $repository->add($due);
        $repository->add($future);
        $repository->add($removed);
        $repository->add(AnnouncementMother::published('Publicada'));
        $repository->add(AnnouncementMother::draft('Borrador'));

        $result = $repository->findScheduledToPublishUntil(new \DateTimeImmutable('2026-06-01 00:00'));

        $this->assertCount(1, $result);
        $this->assertSame('Vencida', $result[0]->title());
    }

    /**
     * @param array<int, Announcement> $announcements
     *
     * @return array<int, string>
     */
    private function titlesOf(array $announcements): array
    {
        return array_map(static fn(Announcement $announcement): string => $announcement->title(), $announcements);
    }

    protected function clearPersistence(): void {}

    abstract protected function createSUT(): AnnouncementRepository;
}
