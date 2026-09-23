<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Event\EventCarbonFootprint;

use App\CarbonFootprint\Application\CarbonFootprintReport\Register\RegisterCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\Register\RegisterCarbonFootprintReportService;
use App\CarbonFootprint\Application\CarbonFootprintReport\Remove\RemoveCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\Remove\RemoveCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintCompleted;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class RegisterReportOnEventCarbonFootprintCompletedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private RegisterCarbonFootprintReportService $registerCarbonFootprintReport,
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

        assert($event instanceof EventCarbonFootprintCompleted);

        $user = $this->loggedUser->__invoke();

        $report = $this->reportRepository->findBySubject(CarbonFootprintReportSubjectType::Event, (string) $event->eventCarbonFootprintId());

        if (null !== $report) {
            if ($report->status()->isInProgress()) {
                return;
            }

            ($this->removeCarbonFootprintReport)(new RemoveCarbonFootprintReportRequest($report->id()), $user);
        }

        ($this->registerCarbonFootprintReport)(new RegisterCarbonFootprintReportRequest(CarbonFootprintReportSubjectType::Event, (string) $event->eventCarbonFootprintId()), $user);
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof EventCarbonFootprintCompleted;
    }
}
