<?php

declare(strict_types=1);

namespace App\DataExchange\Infrastructure\Notification;

use App\DataExchange\Domain\Model\Importation\Importation;
use App\DataExchange\Domain\Model\Importation\ImportationExecuted;
use App\DataExchange\Domain\Model\Importation\ImportationRepository;
use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Application\Notification\Notify\SystemNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class ImportationExecutedNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private SystemNotifier $notifier,
        private ImportationRepository $importationRepository,
        private UserRepository $userRepository,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$event instanceof ImportationExecuted) {
            throw new \RuntimeException('Incorrect event!');
        }

        $this->handleImportationExecuted($event);
    }

    private function handleImportationExecuted(ImportationExecuted $event): void
    {
        $importation = $this->importationRepository->find($event->importationId());

        if (!$importation instanceof Importation) {
            return;
        }

        $userId = $importation->registeredBy();

        if (!$userId instanceof UserId) {
            return;
        }

        $user = $this->userRepository->find($userId);

        if (!$user instanceof User) {
            return;
        }

        $subject = 'Importación de {file} finalizada';
        $subjectParams = ['{file}' => $importation->name()];
        $params = [
            'user' => $user,
            'importation' => $importation,
        ];

        $this->notifier->notify(new SystemNotification(
            recipientId: $userId,
            channel: NotificationChannel::Internal,
            subject: $subject,
            template: '@data_exchange/notification/executed.notification.html.twig',
            params: $params,
            accountId: $user->accountId(),
            subjectParams: $subjectParams,
        ));

        $this->notifier->notify(new SystemNotification(
            recipientId: $userId,
            channel: NotificationChannel::Email,
            subject: $subject,
            template: '@data_exchange/notification/executed.email.html.twig',
            params: $params,
            accountId: $user->accountId(),
            subjectParams: $subjectParams,
        ));
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof ImportationExecuted;
    }
}
