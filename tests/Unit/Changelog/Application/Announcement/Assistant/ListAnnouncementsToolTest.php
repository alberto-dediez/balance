<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\Announcement\Assistant;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Application\Announcement\Assistant\ListAnnouncementsTool;
use App\Changelog\Application\Announcement\List\ListAnnouncementsService;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Security\Domain\Model\User\Role;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementMother;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListAnnouncementsToolTest extends TestCase
{
    private ListAnnouncementsTool $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $authorizer = new AnnouncementAuthorizer();
        $this->sut = new ListAnnouncementsTool(
            new ListAnnouncementsService(
                AnnouncementRepositoryMother::withAnnouncements(
                    AnnouncementMother::draft('Borrador'),
                    AnnouncementMother::published('Publicada A'),
                    AnnouncementMother::published('Publicada B', category: ChangelogCategory::Fix),
                ),
                $authorizer,
            ),
            $authorizer,
        );
    }

    public function testAdminsListEverything(): void
    {
        $result = $this->sut->execute($this->arguments([]), UserMother::adminUser(), $this->context());

        $this->assertSame(3, $result->data['total']);
        $this->assertSame('3 anuncios encontrados', $result->summary);
        $this->assertEqualsCanonicalizing(['id', 'title', 'summary', 'category', 'status', 'date', 'publishedAt', 'scheduledAt', 'hasManual', 'hasVideo'], array_keys($result->data['announcements'][0]));
    }

    public function testFiltersByStatusAndCategory(): void
    {
        $drafts = $this->sut->execute($this->arguments(['status' => 'draft']), UserMother::adminUser(), $this->context());
        $fixes = $this->sut->execute($this->arguments(['category' => 'fix']), UserMother::adminUser(), $this->context());

        $this->assertSame(['Borrador'], array_column($drafts->data['announcements'], 'title'));
        $this->assertSame(['Publicada B'], array_column($fixes->data['announcements'], 'title'));
    }

    public function testUsersOnlySeePublishedAnnouncements(): void
    {
        $result = $this->sut->execute($this->arguments([]), UserMother::fromUsernameAndRole('user@dediez.software', Role::User), $this->context());

        $this->assertSame(2, $result->data['total']);
    }

    public function testIsAReadTool(): void
    {
        $this->assertSame(ToolMode::Read, $this->sut->mode());
        $this->assertTrue($this->sut->isAvailableTo(UserMother::adminUser()));
    }

    /** @param array<string, mixed> $raw */
    private function arguments(array $raw): ToolArguments
    {
        return ToolArgumentsFactory::create($this->sut->parameters(), $raw, $this->context());
    }

    private function context(): ToolContext
    {
        return new ToolContext(new \DateTimeImmutable('2026-11-11 10:00:00'), new \DateTimeZone('Europe/Madrid'));
    }
}
