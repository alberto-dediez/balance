<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Application\Announcement\Notify;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Application\Announcement\Notify\NotifyAnnouncementRequest;
use App\Changelog\Application\Announcement\Notify\NotifyAnnouncementService;
use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementMother;
use App\Tests\Unit\Changelog\Domain\Model\Announcement\AnnouncementRepositoryMother;
use App\Tests\Unit\Changelog\Infrastructure\Notification\SpySystemNotifier;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class NotifyAnnouncementServiceTest extends TestCase
{
    private SpySystemNotifier $notifier;

    private InMemoryUserRepository $userRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->notifier = new SpySystemNotifier();
        $this->userRepository = new InMemoryUserRepository();

        $english = UserMother::fromUsername('english@example.com');
        $english->changePreferredLocale(Locale::English);

        $this->userRepository->add(UserMother::fromUsername('a@example.com'));
        $this->userRepository->add(UserMother::fromUsername('b@example.com'));
        $this->userRepository->add($english);
    }

    public function testNotifiesEveryUserInTheirLocale(): void
    {
        $announcement = AnnouncementMother::withTranslations(Locale::Spanish, Locale::English);
        $announcement->publish(true);

        $response = $this->sut($announcement)(new NotifyAnnouncementRequest($announcement->id()), UserMother::adminUser());

        $this->assertSame(3, $response->recipientCount);
        $this->assertCount(3, $this->notifier->notifications);

        $titles = [];

        foreach ($this->notifier->notifications as $notification) {
            $this->assertSame(NotificationChannel::Internal, $notification->channel);
            $this->assertSame('Nueva novedad: {title}', $notification->subject);
            $this->assertSame('@changelog/announcements/published.notification.html.twig', $notification->template);
            $this->assertSame($announcement, $notification->params['announcement']);

            $recipient = $this->userRepository->find($notification->recipientId);
            $titles[(string) $recipient?->username()] = $notification->subjectParams['{title}'];
        }

        $this->assertSame('Título en', $titles['english@example.com']);
        $this->assertSame('Título es', $titles['a@example.com']);
        $this->assertSame('Título es', $titles['b@example.com']);
    }

    public function testDoesNotNotifyAnUnpublishedAnnouncement(): void
    {
        $announcement = AnnouncementMother::published();
        $announcement->unpublish();

        $response = $this->sut($announcement)(new NotifyAnnouncementRequest($announcement->id()), UserMother::adminUser());

        $this->assertSame(0, $response->recipientCount);
        $this->assertCount(0, $this->notifier->notifications);
    }

    public function testDoesNotNotifyAScheduledAnnouncement(): void
    {
        $announcement = AnnouncementMother::scheduledFor('2030-01-01 10:00', true);

        $response = $this->sut($announcement)(new NotifyAnnouncementRequest($announcement->id()), UserMother::adminUser());

        $this->assertSame(0, $response->recipientCount);
    }

    public function testDoesNotNotifyAnUnknownAnnouncement(): void
    {
        $response = $this->sut(AnnouncementMother::published())(new NotifyAnnouncementRequest(AnnouncementId::generate()), UserMother::adminUser());

        $this->assertSame(0, $response->recipientCount);
    }

    public function testNonAdminsCannotNotify(): void
    {
        $announcement = AnnouncementMother::published();

        $this->expectException(UnauthorizedActionException::class);

        $this->sut($announcement)(new NotifyAnnouncementRequest($announcement->id()), UserMother::ownerUser());
    }

    private function sut(Announcement $announcement): NotifyAnnouncementService
    {
        return new NotifyAnnouncementService(
            AnnouncementRepositoryMother::withAnnouncements($announcement),
            $this->userRepository,
            $this->notifier,
            new AnnouncementAuthorizer(),
        );
    }
}
