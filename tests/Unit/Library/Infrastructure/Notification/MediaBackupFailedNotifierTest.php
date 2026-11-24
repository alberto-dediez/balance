<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Infrastructure\Notification;

use App\Library\Domain\Model\Backup\MediaBackupFailed;
use App\Library\Domain\Model\StorageSync\MediaStorageSynchronized;
use App\Library\Infrastructure\Notification\MediaBackupFailedNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class MediaBackupFailedNotifierTest extends TestCase
{
    private MediaBackupFailedNotifier $sut;

    private InMemoryUserRepository $userRepository;

    private SpySystemNotifier $spyNotifier;

    private User $superAdmin;

    private User $both;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->userRepository = new InMemoryUserRepository();
        $this->spyNotifier = new SpySystemNotifier();

        $this->superAdmin = UserMother::fromUsernameAndRole('superadmin@dediez.software', Role::SuperAdmin);
        $this->both = UserMother::fromUsernameAndRole('both@dediez.software', Role::Admin);
        $this->both->grantRole(Role::SuperAdmin);

        $this->userRepository->add(UserMother::fromUsernameAndRole('admin@dediez.software', Role::Admin));
        $this->userRepository->add(UserMother::fromUsernameAndRole('owner@dediez.software', Role::Owner));
        $this->userRepository->add($this->superAdmin);
        $this->userRepository->add($this->both);

        $this->sut = new MediaBackupFailedNotifier($this->spyNotifier, $this->userRepository);
    }

    public function testIsSubscribedToMediaBackupFailed(): void
    {
        $this->assertTrue($this->sut->isSubscribedTo(new MediaBackupFailed(2, 'Bucket unavailable')));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $this->assertFalse($this->sut->isSubscribedTo(new MediaStorageSynchronized(1)));
    }

    public function testNotifiesEverySuperAdminOnce(): void
    {
        DomainEventPublisher::instance()->publish(new MediaBackupFailed(2, '1111: Bucket unavailable'));

        $this->assertCount(2, $this->spyNotifier->notifications);

        $recipients = [];

        foreach ($this->spyNotifier->notifications as $notification) {
            $this->assertSame(NotificationChannel::Internal, $notification->channel);
            $this->assertSame('La copia de seguridad de la biblioteca ha fallado', $notification->subject);
            $this->assertSame('@library/backup/failed.notification.html.twig', $notification->template);
            $this->assertSame(['failed' => 2, 'error' => '1111: Bucket unavailable'], $notification->params);
            $recipients[] = (string) $notification->recipientId;
        }

        sort($recipients);
        $expected = [(string) $this->superAdmin->id(), (string) $this->both->id()];
        sort($expected);

        $this->assertSame($expected, $recipients);
    }

    public function testDoesNothingWithoutSuperAdmins(): void
    {
        $sut = new MediaBackupFailedNotifier($this->spyNotifier, new InMemoryUserRepository());

        $sut->handle(new MediaBackupFailed(1, 'Bucket unavailable'));

        $this->assertCount(0, $this->spyNotifier->notifications);
    }
}
