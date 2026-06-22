<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Infrastructure\Notification;

use App\Notifications\Application\Notification\Deliver\DeliverNotificationRequest;
use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Notifications\Infrastructure\Notification\DispatchingSystemNotifier;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class DispatchingSystemNotifierTest extends TestCase
{
    private SpyDeliverNotificationHandler $spy;

    protected function setUp(): void
    {
        CommandBusDispatcher::instance()->reset();
        $this->spy = new SpyDeliverNotificationHandler();
        CommandBusDispatcher::instance()->subscribe($this->spy);
    }

    public function testRendersTemplateAndDispatchesSingleEmailDelivery(): void
    {
        $recipient = UserMother::from(UserId::generate(), 'recipient@example.com');
        $sut = $this->notifier($recipient, ['tpl.html.twig' => '<p>Hola {{ name }}</p>']);

        $sut->notify(new SystemNotification(
            recipientId: $recipient->id(),
            channel: NotificationChannel::Email,
            subject: 'Asunto',
            template: 'tpl.html.twig',
            params: ['name' => 'Ana'],
        ));

        $request = $this->dispatchedRequest();

        $this->assertCount(1, $this->spy->requests());
        $this->assertEquals(NotificationChannel::Email, $request->channel);
        $this->assertEquals('<p>Hola Ana</p>', $request->body);
    }

    public function testInternalChannelIsSkippedWhenDisabled(): void
    {
        $recipient = UserMother::from(UserId::generate(), 'recipient@example.com');
        $sut = $this->notifier($recipient, [], internalNotificationsEnabled: false);

        $sut->notify(new SystemNotification(
            recipientId: $recipient->id(),
            channel: NotificationChannel::Internal,
            subject: 'Asunto',
            body: 'Mensaje',
        ));

        $this->assertCount(0, $this->spy->requests());
    }

    public function testInternalChannelUsesPlainBodyWhenEnabled(): void
    {
        $recipient = UserMother::from(UserId::generate(), 'recipient@example.com');
        $sut = $this->notifier($recipient, []);

        $sut->notify(new SystemNotification(
            recipientId: $recipient->id(),
            channel: NotificationChannel::Internal,
            subject: 'Asunto',
            body: 'Mensaje',
        ));

        $request = $this->dispatchedRequest();

        $this->assertCount(1, $this->spy->requests());
        $this->assertEquals(NotificationChannel::Internal, $request->channel);
        $this->assertEquals('Mensaje', $request->body);
    }

    public function testDoesNotDispatchWhenRecipientIsUnknown(): void
    {
        $sut = $this->notifier(null, []);

        $sut->notify(new SystemNotification(
            recipientId: UserId::generate(),
            channel: NotificationChannel::Email,
            subject: 'Asunto',
            body: 'Mensaje',
        ));

        $this->assertCount(0, $this->spy->requests());
    }

    /**
     * @param array<string,string> $templates
     */
    private function notifier(?User $recipient, array $templates, bool $internalNotificationsEnabled = true): DispatchingSystemNotifier
    {
        $userRepository = new InMemoryUserRepository();

        if ($recipient instanceof User) {
            $userRepository->add($recipient);
        }

        return new DispatchingSystemNotifier(
            new Environment(new ArrayLoader($templates)),
            $userRepository,
            $internalNotificationsEnabled,
        );
    }

    private function dispatchedRequest(): DeliverNotificationRequest
    {
        return $this->spy->requests()[0];
    }
}
