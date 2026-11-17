<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\Announcement\Assistant;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Application\Announcement\Assistant\ViewAnnouncementTool;
use App\Changelog\Application\Announcement\View\ViewAnnouncementService;
use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Application\Assistant\InvalidToolArgumentsException;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolResult;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementMother;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ViewAnnouncementToolTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testReturnsTheAnnouncementWithItsManual(): void
    {
        $announcement = AnnouncementMother::withManual(Locale::Spanish);
        $announcement->publish(false);
        $sut = $this->createSUT($announcement);

        $result = $sut->execute($this->arguments($sut, (string) $announcement->id()), UserMother::adminUser(), $this->context());

        $this->assertSame((string) $announcement->id(), $result->data['id']);
        $this->assertSame('Novedad con manual', $result->data['title']);
        $this->assertSame('published', $result->data['status']);
        $this->assertTrue($result->data['hasManual']);
        $this->assertSame((string) $announcement->manualId(), $result->data['manualMediaId']);
        $this->assertFalse($result->data['truncated']);
        $this->assertSame(['announcementId' => (string) $announcement->id()], $result->references);
        $this->assertSame('route:admin_announcements_edit?announcementId=' . $announcement->id(), $result->link?->hrefFrom($result->references));
    }

    public function testTruncatesALongBody(): void
    {
        $announcement = AnnouncementMother::published();
        $announcement->update(
            category: $announcement->category(),
            date: $announcement->date(),
            version: null,
            title: 'Largo',
            summary: 'Resumen',
            body: str_repeat('a', ToolResult::TEXT_MAX_LENGTH + 1),
            youtubeVideo: null,
            manualId: null,
        );
        $sut = $this->createSUT($announcement);

        $result = $sut->execute($this->arguments($sut, (string) $announcement->id()), UserMother::adminUser(), $this->context());

        $this->assertTrue($result->data['truncated']);
        $this->assertSame(ToolResult::TEXT_MAX_LENGTH, mb_strlen($result->data['body']));
    }

    public function testUnknownAnnouncementFails(): void
    {
        $sut = $this->createSUT();

        $this->expectException(InvalidToolArgumentsException::class);
        $this->expectExceptionMessage('El anuncio no existe; búscalo con list_announcements.');

        $sut->execute($this->arguments($sut, (string) AnnouncementId::generate()), UserMother::adminUser(), $this->context());
    }

    public function testUsersCannotViewADraft(): void
    {
        $announcement = AnnouncementMother::draft();
        $sut = $this->createSUT($announcement);

        $this->expectException(UnauthorizedActionException::class);

        $sut->execute($this->arguments($sut, (string) $announcement->id()), UserMother::fromUsernameAndRole('user@dediez.software', Role::User), $this->context());
    }

    public function testIsAReadTool(): void
    {
        $sut = $this->createSUT();

        $this->assertSame(ToolMode::Read, $sut->mode());
        $this->assertTrue($sut->isAvailableTo(UserMother::adminUser()));
    }

    private function createSUT(Announcement ...$announcements): ViewAnnouncementTool
    {
        $authorizer = new AnnouncementAuthorizer();

        return new ViewAnnouncementTool(new ViewAnnouncementService(AnnouncementRepositoryMother::withAnnouncements(...$announcements), $authorizer), $authorizer);
    }

    private function arguments(ViewAnnouncementTool $sut, string $announcementId): ToolArguments
    {
        return ToolArgumentsFactory::create($sut->parameters(), ['announcementId' => $announcementId], $this->context());
    }

    private function context(): ToolContext
    {
        return new ToolContext(new \DateTimeImmutable('2026-11-11 10:00:00'), new \DateTimeZone('Europe/Madrid'));
    }
}
