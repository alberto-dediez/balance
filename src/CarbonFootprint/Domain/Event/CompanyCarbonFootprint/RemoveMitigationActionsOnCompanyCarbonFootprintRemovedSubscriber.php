<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Event\CompanyCarbonFootprint;

use App\CarbonFootprint\Application\MitigationAction\RemoveOfCarbonFootprint\RemoveMitigationActionsOfCarbonFootprintRequest;
use App\CarbonFootprint\Application\MitigationAction\RemoveOfCarbonFootprint\RemoveMitigationActionsOfCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRemoved;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class RemoveMitigationActionsOnCompanyCarbonFootprintRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private RemoveMitigationActionsOfCarbonFootprintService $removeMitigationActionsOfCarbonFootprint,
        private LoggedUser $loggedUser,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof CompanyCarbonFootprintRemoved);

        ($this->removeMitigationActionsOfCarbonFootprint)(
            new RemoveMitigationActionsOfCarbonFootprintRequest(
                subjectType: CarbonFootprintReportSubjectType::Company,
                carbonFootprintId: (string) $event->companyCarbonFootprintId(),
            ),
            $this->loggedUser->__invoke(),
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof CompanyCarbonFootprintRemoved;
    }
}
