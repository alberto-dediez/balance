<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Event\CarbonFootprintReport;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportSubjects;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRejected;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class ReopenCarbonFootprintOnCarbonFootprintReportRejectedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private CarbonFootprintReportRepository $reportRepository,
        private CarbonFootprintReportSubjects $subjects,
        private LoggedUser $loggedUser,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof CarbonFootprintReportRejected);

        $report = $this->reportRepository->find($event->carbonFootprintReportId());

        if (null === $report) {
            return;
        }

        $this->subjects->reopen($report->subjectType(), $report->carbonFootprintId(), $this->loggedUser->__invoke());
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof CarbonFootprintReportRejected;
    }
}
