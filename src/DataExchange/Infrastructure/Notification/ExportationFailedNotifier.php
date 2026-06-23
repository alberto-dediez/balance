<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\Notification;

use App\DataExchange\Domain\Model\Exportation\Exportation;
use App\DataExchange\Domain\Model\Exportation\ExportationFailed;
use App\DataExchange\Domain\Model\Exportation\ExportationRepository;
use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Application\Notification\Notify\SystemNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class ExportationFailedNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private SystemNotifier $notifier,
        private ExportationRepository $exportationRepository,
        private UserRepository $userRepository,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$event instanceof ExportationFailed) {
            throw new \RuntimeException('Incorrect event!');
        }

        $this->handleExportationFailed($event);
    }

    private function handleExportationFailed(ExportationFailed $event): void
    {
        $exportation = $this->exportationRepository->find($event->exportationId());

        if (!$exportation instanceof Exportation) {
            return;
        }

        $userId = $exportation->registeredBy();

        if (!$userId instanceof UserId) {
            return;
        }

        $user = $this->userRepository->find($userId);

        if (!$user instanceof User) {
            return;
        }

        $subject = 'La exportación no se ha podido generar';
        $params = [
            'user' => $user,
            'exportation' => $exportation,
        ];

        $this->notifier->notify(new SystemNotification(
            recipientId: $userId,
            channel: NotificationChannel::Internal,
            subject: $subject,
            template: '@data_exchange/notification/exportation-failed.notification.html.twig',
            params: $params,
            accountId: $user->accountId(),
        ));

        $this->notifier->notify(new SystemNotification(
            recipientId: $userId,
            channel: NotificationChannel::Email,
            subject: $subject,
            template: '@data_exchange/notification/exportation-failed.email.html.twig',
            params: $params,
            accountId: $user->accountId(),
        ));
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof ExportationFailed;
    }
}
