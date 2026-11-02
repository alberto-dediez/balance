<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Event;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerMateriality\AnswerCompanyCarbonFootprintMaterialityRequest;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\AnswerMateriality\AnswerCompanyCarbonFootprintMaterialityService;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\AnswerMateriality\AnswerPropertyCarbonFootprintMaterialityRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\AnswerMateriality\AnswerPropertyCarbonFootprintMaterialityService;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentCompleted;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class AdvanceCarbonFootprintOnMaterialityAssessmentCompletedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private AnswerPropertyCarbonFootprintMaterialityService $answerPropertyCarbonFootprintMateriality,
        private AnswerCompanyCarbonFootprintMaterialityService $answerCompanyCarbonFootprintMateriality,
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

        match ($event->subjectType()) {
            MaterialityAssessmentSubjectType::Property => ($this->answerPropertyCarbonFootprintMateriality)(
                new AnswerPropertyCarbonFootprintMaterialityRequest(new PropertyCarbonFootprintId($event->carbonFootprintId())),
                $this->loggedUser->__invoke(),
            ),
            MaterialityAssessmentSubjectType::Company => ($this->answerCompanyCarbonFootprintMateriality)(
                new AnswerCompanyCarbonFootprintMaterialityRequest(new CompanyCarbonFootprintId($event->carbonFootprintId())),
                $this->loggedUser->__invoke(),
            ),
        };
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof MaterialityAssessmentCompleted;
    }
}
