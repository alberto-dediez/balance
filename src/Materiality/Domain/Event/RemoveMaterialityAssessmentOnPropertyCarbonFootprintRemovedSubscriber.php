<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Event;

use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRemoved;
use App\Materiality\Application\MaterialityAssessment\RemoveOfCarbonFootprint\RemoveMaterialityAssessmentOfCarbonFootprintRequest;
use App\Materiality\Application\MaterialityAssessment\RemoveOfCarbonFootprint\RemoveMaterialityAssessmentOfCarbonFootprintService;
use App\Materiality\Domain\Model\MaterialityAssessment\MaterialityAssessmentSubjectType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class RemoveMaterialityAssessmentOnPropertyCarbonFootprintRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private RemoveMaterialityAssessmentOfCarbonFootprintService $removeMaterialityAssessmentOfCarbonFootprint,
        private LoggedUser $loggedUser,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof PropertyCarbonFootprintRemoved);

        ($this->removeMaterialityAssessmentOfCarbonFootprint)(
            new RemoveMaterialityAssessmentOfCarbonFootprintRequest(
                subjectType: MaterialityAssessmentSubjectType::Property,
                carbonFootprintId: (string) $event->propertyCarbonFootprintId(),
            ),
            $this->loggedUser->__invoke(),
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof PropertyCarbonFootprintRemoved;
    }
}
