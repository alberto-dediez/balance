<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notifications\Application\Notification\Deliver;

use App\Notifications\Application\Notification\Deliver\DeliverNotificationRequest;
use App\Notifications\Application\Notification\Deliver\DeliverNotificationService;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Notifications\Domain\Model\Notification\NotificationStatus;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Notifications\Domain\Model\Notification\NotificationRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class DeliverNotificationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testDeliverInternalCreatesUnreadSentNotification(): void
    {
        $repository = NotificationRepositoryMother::inMemory();
        $emailSender = new SpyEmailSender();

        $sut = new DeliverNotificationService($repository, new InMemoryUserRepository(), $emailSender);

        $recipientId = UserId::generate();

        $response = $sut(new DeliverNotificationRequest(
            recipientId: (string) $recipientId,
            channel: NotificationChannel::Internal,
            subject: 'Hola',
            body: 'Mensaje',
        ), UserMother::adminUser());

        $notification = $repository->find($response->notificationId);

        $this->assertNotNull($notification);
        $this->assertEquals(NotificationStatus::Sent, $notification->status());
        $this->assertFalse($notification->isRead());
        $this->assertCount(0, $emailSender->sent());
    }

    public function testDeliverEmailSendsAndMarksSent(): void
    {
        $repository = NotificationRepositoryMother::inMemory();
        $emailSender = new SpyEmailSender();
        $userRepository = new InMemoryUserRepository();

        $recipient = UserMother::from(UserId::generate(), 'recipient@example.com');
        $userRepository->add($recipient);

        $sut = new DeliverNotificationService($repository, $userRepository, $emailSender);

        $response = $sut(new DeliverNotificationRequest(
            recipientId: (string) $recipient->id(),
            channel: NotificationChannel::Email,
            subject: 'Asunto',
            body: 'Cuerpo',
        ), UserMother::adminUser());

        $notification = $repository->find($response->notificationId);

        $this->assertNotNull($notification);
        $this->assertEquals(NotificationStatus::Sent, $notification->status());
        $this->assertCount(1, $emailSender->sent());
        $this->assertEquals('recipient@example.com', $emailSender->sent()[0]['recipient']->email);
    }

    public function testDeliverEmailWrapsBodyInBaseTemplate(): void
    {
        $repository = NotificationRepositoryMother::inMemory();
        $emailSender = new SpyEmailSender();
        $userRepository = new InMemoryUserRepository();

        $recipient = UserMother::from(UserId::generate(), 'recipient@example.com');
        $userRepository->add($recipient);

        $sut = new DeliverNotificationService($repository, $userRepository, $emailSender);

        $sut(new DeliverNotificationRequest(
            recipientId: (string) $recipient->id(),
            channel: NotificationChannel::Email,
            subject: 'Asunto',
            body: '<p>Cuerpo</p>',
        ), UserMother::adminUser());

        $this->assertCount(1, $emailSender->sent());

        $sent = $emailSender->sent()[0];
        $this->assertEquals('@notifications/email/notification.email.html.twig', $sent['template']);
        $this->assertIsArray($sent['params']);
        $this->assertEquals('<p>Cuerpo</p>', $sent['params']['body']);
    }

    public function testDeliverEmailMarksFailedWhenRecipientNotFound(): void
    {
        $repository = NotificationRepositoryMother::inMemory();
        $emailSender = new SpyEmailSender();

        $sut = new DeliverNotificationService($repository, new InMemoryUserRepository(), $emailSender);

        $response = $sut(new DeliverNotificationRequest(
            recipientId: (string) UserId::generate(),
            channel: NotificationChannel::Email,
            subject: 'Asunto',
            body: 'Cuerpo',
        ), UserMother::adminUser());

        $notification = $repository->find($response->notificationId);

        $this->assertNotNull($notification);
        $this->assertEquals(NotificationStatus::Failed, $notification->status());
        $this->assertCount(0, $emailSender->sent());
    }

    public function testDeliverEmailMarksFailedWhenTransportThrows(): void
    {
        $repository = NotificationRepositoryMother::inMemory();
        $emailSender = new SpyEmailSender(shouldFail: true);
        $userRepository = new InMemoryUserRepository();

        $recipient = UserMother::from(UserId::generate(), 'recipient@example.com');
        $userRepository->add($recipient);

        $sut = new DeliverNotificationService($repository, $userRepository, $emailSender);

        $response = $sut(new DeliverNotificationRequest(
            recipientId: (string) $recipient->id(),
            channel: NotificationChannel::Email,
            subject: 'Asunto',
            body: 'Cuerpo',
        ), UserMother::adminUser());

        $notification = $repository->find($response->notificationId);

        $this->assertNotNull($notification);
        $this->assertEquals(NotificationStatus::Failed, $notification->status());
    }

    public function testDeliverSmsIsNotYetSupported(): void
    {
        $repository = NotificationRepositoryMother::inMemory();

        $sut = new DeliverNotificationService($repository, new InMemoryUserRepository(), new SpyEmailSender());

        $response = $sut(new DeliverNotificationRequest(
            recipientId: (string) UserId::generate(),
            channel: NotificationChannel::Sms,
            subject: 'Asunto',
            body: 'Cuerpo',
        ), UserMother::adminUser());

        $notification = $repository->find($response->notificationId);

        $this->assertNotNull($notification);
        $this->assertEquals(NotificationStatus::Failed, $notification->status());
    }
}
