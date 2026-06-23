<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Notification;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersion;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersionCompleted;
use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Application\Notification\Notify\SystemNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use Symfony\Component\Translation\TranslatableMessage;

class PreventionPlanVersionCompletedNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private SystemNotifier $notifier,
        private PreventionPlanRepository $preventionPlanRepository,
        private UserRepository $userRepository,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof PreventionPlanVersionCompleted);

        $this->handlePreventionPlanVersionCompleted($event);
    }

    private function handlePreventionPlanVersionCompleted(PreventionPlanVersionCompleted $event): void
    {
        $preventionPlan = $this->preventionPlanRepository->find($event->preventionPlanId());

        if (!$preventionPlan instanceof PreventionPlan) {
            return;
        }

        $version = $preventionPlan->versions()->findByVersionNumber($event->versionNumber());

        if (!$version instanceof PreventionPlanVersion) {
            return;
        }

        $user = $this->userRepository->find($version->createdBy());

        if (!$user instanceof User) {
            return;
        }

        $subject = (string) new TranslatableMessage('Plan de prevención publicado');
        $params = [
            'user' => $user,
            'preventionPlan' => $preventionPlan,
            'version' => $version,
        ];

        $this->notifier->notify(new SystemNotification(
            recipientId: $user->id(),
            channel: NotificationChannel::Internal,
            subject: $subject,
            template: '@food_loss/prevention-plan/version-completed.notification.html.twig',
            params: $params,
            accountId: $user->accountId(),
        ));

        $this->notifier->notify(new SystemNotification(
            recipientId: $user->id(),
            channel: NotificationChannel::Email,
            subject: $subject,
            template: '@food_loss/prevention-plan/version-completed.email.html.twig',
            params: $params,
            accountId: $user->accountId(),
        ));
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof PreventionPlanVersionCompleted;
    }
}
