<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Application\Notification\Assistant;

use App\Notifications\Application\Notification\Assistant\ListMyNotificationsTool;
use App\Notifications\Application\Notification\List\ListNotificationsService;
use App\Notifications\Application\Notification\NotificationAuthorizer;
use App\Notifications\Infrastructure\Persistence\InMemory\Repository\InMemoryNotificationRepository;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolResult;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Notifications\Domain\Model\Notification\NotificationMother;
use App\Tests\Unit\Notifications\Domain\Model\Notification\NotificationRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListMyNotificationsToolTest extends TestCase
{
    private InMemoryNotificationRepository $notifications;

    private ListMyNotificationsTool $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->notifications = NotificationRepositoryMother::inMemory();
        $authorizer = new NotificationAuthorizer();
        $this->sut = new ListMyNotificationsTool(new ListNotificationsService($this->notifications, $authorizer), $authorizer);
    }

    public function testIsAReadToolAvailableOnlyToAdmins(): void
    {
        $this->assertSame('list_my_notifications', $this->sut->name());
        $this->assertSame(ToolMode::Read, $this->sut->mode());
        $this->assertTrue($this->sut->isAvailableTo(UserMother::adminUser()));
        $this->assertFalse($this->sut->isAvailableTo(UserMother::registeredUser()));
    }

    public function testListsOnlyTheNotificationsOfTheUser(): void
    {
        $admin = UserMother::adminUser();
        $this->notifications->add(NotificationMother::from($admin->id(), subject: 'Mía'));
        $this->notifications->add(NotificationMother::from(UserMother::registeredUser()->id(), subject: 'Ajena'));

        $result = $this->sut->execute($this->arguments([]), $admin, $this->context());

        $this->assertSame(1, $result->data['total']);
        $this->assertSame('Mía', $result->data['notifications'][0]['subject']);
        $this->assertFalse($result->data['notifications'][0]['read']);
        $this->assertSame(['subject', 'createdAt', 'read'], array_keys($result->data['notifications'][0]));
    }

    public function testFiltersTheUnreadNotifications(): void
    {
        $admin = UserMother::adminUser();
        $read = NotificationMother::from($admin->id(), subject: 'Leída');
        $read->markAsRead();
        $this->notifications->add($read);
        $this->notifications->add(NotificationMother::from($admin->id(), subject: 'Pendiente'));

        $result = $this->sut->execute($this->arguments(['onlyUnread' => true]), $admin, $this->context());

        $this->assertSame(1, $result->data['total']);
        $this->assertSame('Pendiente', $result->data['notifications'][0]['subject']);
    }

    public function testReturnsAtMostTheMaximumNumberOfItems(): void
    {
        $admin = UserMother::adminUser();

        for ($i = 0; $i < ToolResult::ITEMS_MAX_COUNT + 5; ++$i) {
            $this->notifications->add(NotificationMother::internalFor($admin->id()));
        }

        $result = $this->sut->execute($this->arguments(['limit' => 50]), $admin, $this->context());

        $this->assertCount(ToolResult::ITEMS_MAX_COUNT, $result->data['notifications']);
    }

    /** @param array<string, mixed> $raw */
    private function arguments(array $raw): ToolArguments
    {
        return ToolArgumentsFactory::create($this->sut->parameters(), $raw, $this->context());
    }

    private function context(): ToolContext
    {
        return new ToolContext(new \DateTimeImmutable('2026-11-16 10:00:00'), new \DateTimeZone('Europe/Madrid'));
    }
}
