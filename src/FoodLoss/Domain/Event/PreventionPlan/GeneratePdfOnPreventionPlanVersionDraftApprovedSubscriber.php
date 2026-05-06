<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\PreventionPlan;

use App\FoodLoss\Application\PreventionPlan\GeneratePdf\GeneratePdfPreventionPlanRequest;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanVersionDraftApproved;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use App\Shared\Domain\Model\Locale\Locale;

final class GeneratePdfOnPreventionPlanVersionDraftApprovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    /** @var Locale[] */
    private array $locales;

    public function __construct(private LoggedUser $loggedUser, string $localeList)
    {
        $this->locales = array_values(array_filter(
            array_map(
                static fn(string $value) => Locale::tryFrom(trim($value)),
                explode('|', $localeList),
            ),
        ));

        $this->register();
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof PreventionPlanVersionDraftApproved;
    }

    public function handle(DomainEvent $event): void
    {
        assert($event instanceof PreventionPlanVersionDraftApproved);

        $user = $this->loggedUser->__invoke();

        foreach ($this->locales as $locale) {
            CommandBusDispatcher::instance()->dispatch(
                new DeferredCommand(
                    command: new GeneratePdfPreventionPlanRequest(
                        preventionPlanId: $event->preventionPlanId(),
                        versionNumber: $event->versionNumber(),
                        locale: $locale,
                    ),
                ),
                $user,
            );
        }
    }
}
