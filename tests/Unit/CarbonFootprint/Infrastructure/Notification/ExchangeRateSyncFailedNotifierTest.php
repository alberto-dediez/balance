<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Notification;

use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateId;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateSyncFailed;
use App\CarbonFootprint\Domain\Model\ExchangeRate\ExchangeRateUpdated;
use App\CarbonFootprint\Infrastructure\Notification\ExchangeRateSyncFailedNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Notifications\Application\Notification\Notify\SpySystemNotifier;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ExchangeRateSyncFailedNotifierTest extends TestCase
{
    private SpySystemNotifier $notifier;
    private InMemoryUserRepository $users;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->notifier = new SpySystemNotifier();
        $this->users = new InMemoryUserRepository();

        $this->users->add(UserMother::fromUsernameAndRole('super@dediez.software', Role::SuperAdmin));
        $this->users->add(UserMother::fromUsernameAndRole('admin@dediez.software', Role::Admin));
        $this->users->add(UserMother::fromUsernameAndRole('owner@dediez.software', Role::Owner));
        $this->users->add(UserMother::fromUsernameAndRole('user@dediez.software', Role::User));
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testNotifiesEveryAdministratorThroughTheInternalChannel(): void
    {
        $this->createSUT()->handle($this->event());

        $notifications = $this->notifier->notifications();

        $this->assertCount(2, $notifications);

        foreach ($notifications as $notification) {
            $this->assertSame(NotificationChannel::Internal, $notification->channel);
            $this->assertSame('No se pudieron descargar los tipos de cambio del BCE', $notification->subject);
            $this->assertSame('@carbon_footprint/notification/exchange-rates-sync-failed.notification.html.twig', $notification->template);
            $this->assertSame('Source unavailable', $notification->params['reason']);
        }
    }

    public function testDoesNotNotifyOwnersNorUsers(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->users->add(UserMother::fromUsernameAndRole('owner@dediez.software', Role::Owner));
        $this->users->add(UserMother::fromUsernameAndRole('user@dediez.software', Role::User));

        $this->createSUT()->handle($this->event());

        $this->assertCount(0, $this->notifier->notifications());
    }

    public function testIsOnlySubscribedToSyncFailures(): void
    {
        $this->assertTrue($this->createSUT()->isSubscribedTo($this->event()));
        $this->assertFalse($this->createSUT()->isSubscribedTo(new ExchangeRateUpdated(
            exchangeRateId: ExchangeRateId::generate(),
            date: new \DateTimeImmutable('2026-10-14'),
            rate: '1.25',
            fetchedAt: new \DateTimeImmutable('NOW'),
        )));
    }

    private function event(): ExchangeRateSyncFailed
    {
        return new ExchangeRateSyncFailed(
            reason: 'Source unavailable',
            attemptedAt: new \DateTimeImmutable('2026-10-14 17:00:00'),
        );
    }

    private function createSUT(): ExchangeRateSyncFailedNotifier
    {
        return new ExchangeRateSyncFailedNotifier($this->notifier, $this->users);
    }
}
