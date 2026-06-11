<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Notification;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersion;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersionCompleted;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use App\Shared\Domain\Model\Email\EmailRecipient;
use App\Shared\Domain\Model\Email\EmailSender;
use Symfony\Contracts\Translation\TranslatorInterface;

class PreventionPlanVersionCompletedMailer implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private EmailSender $emailSender,
        private PreventionPlanRepository $preventionPlanRepository,
        private UserRepository $userRepository,
        private TranslatorInterface $translator,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$event instanceof PreventionPlanVersionCompleted) {
            throw new \RuntimeException('Incorrect event!');
        }

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

        $this->emailSender->__invoke(
            recipient: new EmailRecipient(name: $user->username(), email: $user->username()),
            subject: $this->translator->trans('Plan de prevención publicado'),
            template: '@food_loss/prevention-plan/version-completed.email.html.twig',
            params: [
                'user' => $user,
                'preventionPlan' => $preventionPlan,
                'version' => $version,
            ],
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof PreventionPlanVersionCompleted;
    }
}
