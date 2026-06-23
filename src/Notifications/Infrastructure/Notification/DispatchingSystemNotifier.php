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
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class DispatchingSystemNotifier implements SystemNotifier
{
    public function __construct(
        private readonly Environment $twig,
        private readonly UserRepository $userRepository,
        private readonly TranslatorInterface $translator,
        private readonly LocaleSwitcher $localeSwitcher,
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

        $locale = $recipient->preferredLocale()?->value ?? $this->localeSwitcher->getLocale();

        $this->localeSwitcher->runWithLocale($locale, function () use ($notification, $recipient): void {
            $subject = $this->translator->trans($notification->subject, $notification->subjectParams);

            $body = $notification->hasTemplate()
                ? $this->twig->render((string) $notification->template, $notification->params)
                : (string) $notification->body;

            CommandBusDispatcher::instance()->dispatch(
                new DeliverNotificationRequest(
                    recipientId: (string) $notification->recipientId,
                    channel: $notification->channel,
                    subject: $subject,
                    body: $body,
                    accountId: null !== $notification->accountId ? (string) $notification->accountId : null,
                    sentBy: null,
                ),
                $recipient,
            );
        });
    }
}
