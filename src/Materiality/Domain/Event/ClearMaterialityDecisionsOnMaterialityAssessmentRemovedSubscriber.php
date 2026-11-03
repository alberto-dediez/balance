<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Event;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\ClearMaterialityDecisions\ClearMaterialityDecisionsFromCompanyCarbonFootprintRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\ClearMaterialityDecisions\ClearMaterialityDecisionsFromCompanyCarbonFootprintService;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\ClearMaterialityDecisions\ClearMaterialityDecisionsFromPropertyCarbonFootprintRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\ClearMaterialityDecisions\ClearMaterialityDecisionsFromPropertyCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentRemoved;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class ClearMaterialityDecisionsOnMaterialityAssessmentRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private ClearMaterialityDecisionsFromPropertyCarbonFootprintService $clearFromPropertyCarbonFootprint,
        private ClearMaterialityDecisionsFromCompanyCarbonFootprintService $clearFromCompanyCarbonFootprint,
        private LoggedUser $loggedUser,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof MaterialityAssessmentRemoved);

        $carbonFootprintId = $event->carbonFootprintId();

        if (null === $carbonFootprintId || '' === $carbonFootprintId) {
            return;
        }

        match ($event->subjectType()) {
            MaterialityAssessmentSubjectType::Property => ($this->clearFromPropertyCarbonFootprint)(
                new ClearMaterialityDecisionsFromPropertyCarbonFootprintRequest(new PropertyCarbonFootprintId($carbonFootprintId)),
                $this->loggedUser->__invoke(),
            ),
            MaterialityAssessmentSubjectType::Company => ($this->clearFromCompanyCarbonFootprint)(
                new ClearMaterialityDecisionsFromCompanyCarbonFootprintRequest(new CompanyCarbonFootprintId($carbonFootprintId)),
                $this->loggedUser->__invoke(),
            ),
            null => null,
        };
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof MaterialityAssessmentRemoved;
    }
}
