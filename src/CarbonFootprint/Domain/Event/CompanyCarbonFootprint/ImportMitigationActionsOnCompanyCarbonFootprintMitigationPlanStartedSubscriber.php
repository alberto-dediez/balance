<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Event\CompanyCarbonFootprint;

use App\CarbonFootprint\Application\MitigationAction\Import\ImportMitigationActionsRequest;
use App\CarbonFootprint\Application\MitigationAction\Import\ImportMitigationActionsService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMitigationPlanStarted;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class ImportMitigationActionsOnCompanyCarbonFootprintMitigationPlanStartedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private ImportMitigationActionsService $importMitigationActions, private LoggedUser $loggedUser)
    {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof CompanyCarbonFootprintMitigationPlanStarted);

        ($this->importMitigationActions)(
            new ImportMitigationActionsRequest(
                subjectType: CarbonFootprintReportSubjectType::Company,
                carbonFootprintId: (string) $event->companyCarbonFootprintId(),
                onlyIfEmpty: true,
            ),
            $this->loggedUser->__invoke(),
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof CompanyCarbonFootprintMitigationPlanStarted;
    }
}
