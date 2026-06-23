<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Notification;

use App\FoodLoss\Domain\Model\Incidence\IncidenceRegistered;
use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Application\Notification\Notify\SystemNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use Symfony\Component\Translation\TranslatableMessage;

class IncidenceRegisteredNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private readonly SystemNotifier $notifier,
        private readonly UserRepository $userRepository,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof IncidenceRegistered);

        $this->handleIncidenceRegistered($event);
    }

    private function handleIncidenceRegistered(IncidenceRegistered $event): void
    {
        $accountId = $event->accountId();

        $subject = (string) new TranslatableMessage('Nueva incidencia registrada');
        $params = [
            'incidence' => $event,
        ];

        $owners = array_filter(
            $this->userRepository->findByAccountId($accountId),
            static fn(User $user): bool => $user->hasRole(Role::Owner) && !$user->isRemoved(),
        );

        foreach ($owners as $owner) {
            $this->notifier->notify(new SystemNotification(
                recipientId: $owner->id(),
                channel: NotificationChannel::Internal,
                subject: $subject,
                template: '@food_loss/incidences/registered.notification.html.twig',
                params: $params,
                accountId: $owner->accountId(),
            ));
        }
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof IncidenceRegistered;
    }
}
