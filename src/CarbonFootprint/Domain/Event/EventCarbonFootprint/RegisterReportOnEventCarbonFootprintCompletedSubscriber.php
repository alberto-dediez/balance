<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Event\EventCarbonFootprint;

use App\CarbonFootprint\Application\EventCarbonFootprintReport\Register\RegisterEventCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\EventCarbonFootprintReport\Register\RegisterEventCarbonFootprintReportService;
use App\CarbonFootprint\Application\EventCarbonFootprintReport\Remove\RemoveEventCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\EventCarbonFootprintReport\Remove\RemoveEventCarbonFootprintReportService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintCompleted;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class RegisterReportOnEventCarbonFootprintCompletedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private RegisterEventCarbonFootprintReportService $registerEventCarbonFootprintReport,
        private RemoveEventCarbonFootprintReportService $removeEventCarbonFootprintReport,
        private EventCarbonFootprintReportRepository $reportRepository,
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

        $report = $this->reportRepository->findByEventCarbonFootprintId($event->eventCarbonFootprintId());

        if (null !== $report) {
            if ($report->status()->isPending()) {
                return;
            }

            ($this->removeEventCarbonFootprintReport)(new RemoveEventCarbonFootprintReportRequest($report->id()), $user);
        }

        ($this->registerEventCarbonFootprintReport)(new RegisterEventCarbonFootprintReportRequest($event->eventCarbonFootprintId()), $user);
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof EventCarbonFootprintCompleted;
    }
}
