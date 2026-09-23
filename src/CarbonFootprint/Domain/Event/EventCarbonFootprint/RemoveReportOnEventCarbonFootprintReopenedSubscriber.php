<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Event\EventCarbonFootprint;

use App\CarbonFootprint\Application\CarbonFootprintReport\Remove\RemoveCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\Remove\RemoveCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintReopened;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class RemoveReportOnEventCarbonFootprintReopenedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private RemoveCarbonFootprintReportService $removeCarbonFootprintReport,
        private CarbonFootprintReportRepository $reportRepository,
        private LoggedUser $loggedUser,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof EventCarbonFootprintReopened);

        $report = $this->reportRepository->findBySubject(CarbonFootprintReportSubjectType::Event, (string) $event->eventCarbonFootprintId());

        if (null === $report || $report->status()->isRejected()) {
            return;
        }

        ($this->removeCarbonFootprintReport)(
            new RemoveCarbonFootprintReportRequest($report->id()),
            $this->loggedUser->__invoke(),
        );
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof EventCarbonFootprintReopened;
    }
}
