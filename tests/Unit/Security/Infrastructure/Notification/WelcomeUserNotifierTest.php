<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Notification;

use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRegistered;
use App\Security\Infrastructure\Notification\WelcomeUserNotifier;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Notifications\Application\Notification\Notify\SpySystemNotifier;
use PHPUnit\Framework\TestCase;

class WelcomeUserNotifierTest extends TestCase
{
    private SpySystemNotifier $notifier;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->notifier = new SpySystemNotifier();
    }

    protected function tearDown(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testWelcomesThroughInternalAndEmailChannels(): void
    {
        $this->createSUT()->handle($this->event(welcome: true));

        $notifications = $this->notifier->notifications();

        $this->assertCount(2, $notifications);
        $this->assertSame(NotificationChannel::Internal, $notifications[0]->channel);
        $this->assertSame(NotificationChannel::Email, $notifications[1]->channel);
    }

    public function testDoesNotNotifyWithoutWelcome(): void
    {
        $this->createSUT()->handle($this->event(welcome: false));

        $this->assertCount(0, $this->notifier->notifications());
    }

    private function event(bool $welcome): UserRegistered
    {
        return new UserRegistered(
            userId: UserId::generate(),
            username: 'an@email.com',
            password: 'password',
            accountId: AccountId::generate(),
            welcome: $welcome,
        );
    }

    private function createSUT(): WelcomeUserNotifier
    {
        return new WelcomeUserNotifier($this->notifier);
    }
}
