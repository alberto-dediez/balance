<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Event;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\ApplyMaterialityDecisions\ApplyMaterialityDecisionsToCompanyCarbonFootprintRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\ApplyMaterialityDecisions\ApplyMaterialityDecisionsToCompanyCarbonFootprintService;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\ApplyMaterialityDecisions\ApplyMaterialityDecisionsToPropertyCarbonFootprintRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\ApplyMaterialityDecisions\ApplyMaterialityDecisionsToPropertyCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Materiality\Application\MaterialityAssessment\MaterialityDecisionInputsMapper;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentCompleted;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class ApplyMaterialityDecisionsOnMaterialityAssessmentCompletedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private ApplyMaterialityDecisionsToPropertyCarbonFootprintService $applyToPropertyCarbonFootprint,
        private ApplyMaterialityDecisionsToCompanyCarbonFootprintService $applyToCompanyCarbonFootprint,
        private MaterialityDecisionInputsMapper $mapper,
        private LoggedUser $loggedUser,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof MaterialityAssessmentCompleted);

        $decisions = $this->mapper->fromCompleted($event);

        match ($event->subjectType()) {
            MaterialityAssessmentSubjectType::Property => ($this->applyToPropertyCarbonFootprint)(
                new ApplyMaterialityDecisionsToPropertyCarbonFootprintRequest(new PropertyCarbonFootprintId($event->carbonFootprintId()), $decisions),
                $this->loggedUser->__invoke(),
            ),
            MaterialityAssessmentSubjectType::Company => ($this->applyToCompanyCarbonFootprint)(
                new ApplyMaterialityDecisionsToCompanyCarbonFootprintRequest(new CompanyCarbonFootprintId($event->carbonFootprintId()), $decisions),
                $this->loggedUser->__invoke(),
            ),
        };
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof MaterialityAssessmentCompleted;
    }
}
