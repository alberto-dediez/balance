<?php

declare(strict_types=1);

namespace App\Tests\Unit\Library\Infrastructure\Notification;

use App\Library\Domain\Model\StorageSync\MediaStorageFallbackActivated;
use App\Library\Domain\Model\StorageSync\MediaStorageSynchronized;
use App\Library\Infrastructure\Notification\MediaStorageFallbackActivatedNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class MediaStorageFallbackActivatedNotifierTest extends TestCase
{
    private MediaStorageFallbackActivatedNotifier $sut;

    private InMemoryUserRepository $userRepository;

    private SpySystemNotifier $spyNotifier;

    private User $owner;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->userRepository = new InMemoryUserRepository();
        $this->spyNotifier = new SpySystemNotifier();

        $both = UserMother::fromUsernameAndRole('both@dediez.software', Role::Admin);
        $both->grantRole(Role::SuperAdmin);
        $this->owner = UserMother::fromUsernameAndRole('owner@dediez.software', Role::Owner);

        $this->userRepository->add(UserMother::fromUsernameAndRole('admin@dediez.software', Role::Admin));
        $this->userRepository->add(UserMother::fromUsernameAndRole('superadmin@dediez.software', Role::SuperAdmin));
        $this->userRepository->add($both);
        $this->userRepository->add($this->owner);

        $this->sut = new MediaStorageFallbackActivatedNotifier($this->spyNotifier, $this->userRepository);
    }

    public function testIsSubscribedToMediaStorageFallbackActivated(): void
    {
        $this->assertTrue($this->sut->isSubscribedTo(new MediaStorageFallbackActivated('shared/file.png', 'Unable to store media file at "shared/file.png"')));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $this->assertFalse($this->sut->isSubscribedTo(new MediaStorageSynchronized(1)));
    }

    public function testNotifiesEveryAdministratorOnce(): void
    {
        DomainEventPublisher::instance()->publish(new MediaStorageFallbackActivated('shared/file.png', 'Unable to store media file at "shared/file.png"'));

        $this->assertCount(3, $this->spyNotifier->notifications);

        foreach ($this->spyNotifier->notifications as $notification) {
            $this->assertSame(NotificationChannel::Internal, $notification->channel);
            $this->assertSame('El almacenamiento de la biblioteca no está disponible', $notification->subject);
            $this->assertSame('@library/storage/fallback-activated.notification.html.twig', $notification->template);
            $this->assertFalse($this->owner->id()->equals($notification->recipientId));
        }

        $notification = $this->spyNotifier->notifications[0];

        $this->assertSame(['path' => 'shared/file.png', 'error' => 'Unable to store media file at "shared/file.png"'], $notification->params);
    }

    public function testDoesNothingWithoutAdministrators(): void
    {
        $sut = new MediaStorageFallbackActivatedNotifier($this->spyNotifier, new InMemoryUserRepository());

        $sut->handle(new MediaStorageFallbackActivated('shared/file.png', 'Unable to store media file at "shared/file.png"'));

        $this->assertCount(0, $this->spyNotifier->notifications);
    }
}
