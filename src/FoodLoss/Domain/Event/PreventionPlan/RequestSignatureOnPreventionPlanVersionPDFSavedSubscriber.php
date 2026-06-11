<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\PreventionPlan;

use App\FoodLoss\Application\PreventionPlan\RequestSignature\RequestSignaturePreventionPlanRequest;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlan;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersionPDFSaved;
use App\HumanResources\Domain\Model\Employee\EmployeeRepository;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\LocalizedCommand;
use App\Shared\Application\CommandBus\Priority;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use App\Shared\Domain\Model\Locale\Locale;

final class RequestSignatureOnPreventionPlanVersionPDFSavedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private string $uploadsDir,
        private LoggedUser $loggedUser,
        private PreventionPlanRepository $preventionPlanRepository,
        private EmployeeRepository $employeeRepository,
        private UserRepository $userRepository,
    ) {
        $this->register();
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof PreventionPlanVersionPDFSaved;
    }

    public function handle(DomainEvent $event): void
    {
        assert($event instanceof PreventionPlanVersionPDFSaved);

        $user = $this->loggedUser->__invoke();

        $preventionPlan = $this->preventionPlanRepository->find($event->preventionPlanId());

        if (null === $preventionPlan) {
            return;
        }

        $locale = $this->resolveLocaleFor($user, $preventionPlan);

        if ($event->locale() !== $locale) {
            return;
        }

        CommandBusDispatcher::instance()->dispatch(
            new DeferredCommand(
                command: new LocalizedCommand(
                    command: new RequestSignaturePreventionPlanRequest(
                        preventionPlanId: $event->preventionPlanId(),
                        versionNumber: $event->versionNumber(),
                        pdfPath: sprintf('%s/%s', $this->uploadsDir, $event->pdfPath()),
                    ),
                    locale: $locale,
                ),
                priority: Priority::HIGH,
            ),
            $user,
        );
    }

    private function resolveLocaleFor(User $loggedUser, PreventionPlan $preventionPlan): Locale
    {
        $defaultLocale = $loggedUser->preferredLocale() ?? Locale::Spanish;
        $responsibleId = $preventionPlan->responsible();

        if (null === $responsibleId) {
            return $defaultLocale;
        }

        $responsible = $this->employeeRepository->find($responsibleId);

        if (null === $responsible || null === $responsible->userId()) {
            return $defaultLocale;
        }

        $responsibleUser = $this->userRepository->find($responsible->userId());

        if (null === $responsibleUser) {
            return $defaultLocale;
        }

        return $responsibleUser->preferredLocale() ?? $defaultLocale;
    }
}
