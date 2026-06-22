<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\Notification;

use App\Notifications\Application\Notification\Deliver\DeliverNotificationRequest;
use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Application\Notification\Notify\SystemNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use Twig\Environment;

class DispatchingSystemNotifier implements SystemNotifier
{
    public function __construct(
        private readonly Environment $twig,
        private readonly UserRepository $userRepository,
        private readonly bool $internalNotificationsEnabled = true,
    ) {}

    public function notify(SystemNotification $notification): void
    {
        if (NotificationChannel::Internal === $notification->channel && !$this->internalNotificationsEnabled) {
            return;
        }

        $recipient = $this->userRepository->find($notification->recipientId);

        if (!$recipient instanceof User) {
            return;
        }

        $body = $notification->hasTemplate()
            ? $this->twig->render((string) $notification->template, $notification->params)
            : (string) $notification->body;

        CommandBusDispatcher::instance()->dispatch(
            new DeliverNotificationRequest(
                recipientId: (string) $notification->recipientId,
                channel: $notification->channel,
                subject: $notification->subject,
                body: $body,
                accountId: null !== $notification->accountId ? (string) $notification->accountId : null,
                sentBy: null,
            ),
            $recipient,
        );
    }
}
