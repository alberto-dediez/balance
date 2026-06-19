<?php

declare(strict_types=1);

namespace App\Notifications\Application\Notification\Deliver;

use App\Notifications\Domain\Model\Notification\Notification;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Notifications\Domain\Model\Notification\NotificationId;
use App\Notifications\Domain\Model\Notification\NotificationRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Email\EmailRecipient;
use App\Shared\Domain\Model\Email\EmailSender;

class DeliverNotificationService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly UserRepository $userRepository,
        private readonly EmailSender $emailSender,
    ) {}

    public function __invoke(DeliverNotificationRequest $request, User $user): DeliverNotificationResponse
    {
        $notification = new Notification(NotificationId::generate());

        $notification->create(
            recipientId: UserId::fromString($request->recipientId),
            channel: $request->channel,
            subject: $request->subject,
            body: $request->body,
            accountId: null !== $request->accountId ? AccountId::fromString($request->accountId) : null,
            sentBy: null !== $request->sentBy ? UserId::fromString($request->sentBy) : null,
        );

        $this->deliver($notification);

        $this->notificationRepository->add($notification);

        return new DeliverNotificationResponse(notificationId: $notification->id());
    }

    private function deliver(Notification $notification): void
    {
        if (NotificationChannel::Internal === $notification->channel()) {
            $notification->markAsSent();

            return;
        }

        if (NotificationChannel::Email === $notification->channel()) {
            $this->deliverEmail($notification);

            return;
        }

        // SMS and WhatsApp are not yet supported: the User aggregate does not store a phone number.
        $notification->markAsFailed('Channel not yet supported');
    }

    private function deliverEmail(Notification $notification): void
    {
        $recipientId = $notification->recipientId();

        if (!$recipientId instanceof UserId) {
            $notification->markAsFailed('Missing recipient');

            return;
        }

        $recipient = $this->userRepository->find($recipientId);

        if (!$recipient instanceof User) {
            $notification->markAsFailed('Recipient not found');

            return;
        }

        try {
            ($this->emailSender)(
                recipient: new EmailRecipient(name: $recipient->username(), email: $recipient->username()),
                subject: $notification->subject(),
                template: '@notifications/email/notification.email.html.twig',
                params: [
                    'subject' => $notification->subject(),
                    'body' => $notification->body(),
                ],
            );

            $notification->markAsSent();
        } catch (\Throwable $exception) {
            $notification->markAsFailed($exception->getMessage());
        }
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof DeliverNotificationRequest;
    }
}
