<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Event\PropertyCarbonFootprint;

use App\CarbonFootprint\Application\CarbonFootprintReport\Remove\RemoveCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\Remove\RemoveCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRemoved;
use App\Security\Application\User\SystemActor\SystemActorResolver;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class RemoveReportOnPropertyCarbonFootprintRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private RemoveCarbonFootprintReportService $removeCarbonFootprintReport,
        private CarbonFootprintReportRepository $reportRepository,
        private SystemActorResolver $systemActorResolver,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof PropertyCarbonFootprintRemoved);

        $report = $this->reportRepository->findBySubject(CarbonFootprintReportSubjectType::Property, (string) $event->propertyCarbonFootprintId());

        if (null === $report) {
            return;
        }

        ($this->removeCarbonFootprintReport)(
            new RemoveCarbonFootprintReportRequest($report->id()),
            $this->systemActorResolver->__invoke(),
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof PropertyCarbonFootprintRemoved;
    }
}
