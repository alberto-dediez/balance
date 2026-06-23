<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Notification;

use App\FoodLoss\Domain\Model\Surplus\Surplus;
use App\FoodLoss\Domain\Model\Surplus\SurplusEliminationAssessed;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
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

class SurplusEliminationAssessedNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private readonly SystemNotifier $notifier,
        private readonly SurplusRepository $surplusRepository,
        private readonly UserRepository $userRepository,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof SurplusEliminationAssessed);

        $this->handleSurplusEliminationAssessed($event);
    }

    private function handleSurplusEliminationAssessed(SurplusEliminationAssessed $event): void
    {
        if (!$event->canBeEliminated()) {
            return;
        }

        $surplus = $this->surplusRepository->find($event->surplusId());

        if (!$surplus instanceof Surplus) {
            return;
        }

        $accountId = $surplus->accountId();

        $subject = (string) new TranslatableMessage('Excedente asignado a eliminación');
        $params = [
            'surplus' => $surplus,
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
                template: '@food_loss/surpluses/elimination-assessed.notification.html.twig',
                params: $params,
                accountId: $owner->accountId(),
            ));
        }
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof SurplusEliminationAssessed;
    }
}
