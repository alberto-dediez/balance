<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Infrastructure\Notification;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalog;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogItemId;
use App\Bioscore\Domain\Model\BioscoreCatalogItem\BioscoreCatalogSyncCompleted;
use App\Bioscore\Infrastructure\Notification\BioscoreCatalogSyncCompletedNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Notifications\Application\Notification\Notify\SpySystemNotifier;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class BioscoreCatalogSyncCompletedNotifierTest extends TestCase
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
            $this->assertSame('@bioscore/notification/catalog-sync-completed.notification.html.twig', $notification->template);
        }
    }

    public function testDoesNotSendEmails(): void
    {
        $this->createSUT()->handle($this->event());

        foreach ($this->notifier->notifications() as $notification) {
            $this->assertNotSame(NotificationChannel::Email, $notification->channel);
        }
    }

    public function testTheParamsCarryTheCountsAndTheRoutes(): void
    {
        $this->createSUT()->handle($this->event());

        $params = $this->notifier->notifications()[0]->params;

        $this->assertSame(1, $params['newPendingCount']);
        $this->assertSame(0, $params['invalidatedCount']);
        $this->assertSame([], $params['failedCatalogs']);
        $this->assertSame('admin_bioscore_facility_fuels', $params['routes'][BioscoreCatalog::FacilityFuel->value]);
    }

    private function event(): BioscoreCatalogSyncCompleted
    {
        return new BioscoreCatalogSyncCompleted(
            newPending: [[
                'itemId' => (string) BioscoreCatalogItemId::generate(),
                'catalog' => BioscoreCatalog::FacilityFuel->value,
                'bioscoreId' => 7,
                'name' => 'Gas Natural',
            ]],
            invalidated: [],
            failedCatalogs: [],
            registered: 1,
            updated: 0,
            autoMapped: 0,
            withdrawn: 0,
            restored: 0,
        );
    }

    private function createSUT(): BioscoreCatalogSyncCompletedNotifier
    {
        return new BioscoreCatalogSyncCompletedNotifier($this->notifier, $this->users);
    }
}
