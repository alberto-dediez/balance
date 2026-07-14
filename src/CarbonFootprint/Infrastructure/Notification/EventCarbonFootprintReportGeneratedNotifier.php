<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Notification;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportGenerated;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRepository;
use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Application\Notification\Notify\SystemNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use Symfony\Component\Translation\TranslatableMessage;

class EventCarbonFootprintReportGeneratedNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private SystemNotifier $notifier,
        private EventCarbonFootprintReportRepository $reportRepository,
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private UserRepository $userRepository,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$event instanceof EventCarbonFootprintReportGenerated) {
            throw new \RuntimeException('Incorrect event!');
        }

        $report = $this->reportRepository->find($event->eventCarbonFootprintReportId());

        if (!$report instanceof EventCarbonFootprintReport) {
            return;
        }

        $userId = $report->requestedBy();

        if (!$userId instanceof UserId) {
            return;
        }

        $user = $this->userRepository->find($userId);

        if (!$user instanceof User) {
            return;
        }

        $eventCarbonFootprintId = $report->eventCarbonFootprintId();
        $eventCarbonFootprint = null !== $eventCarbonFootprintId ? $this->eventCarbonFootprintRepository->find($eventCarbonFootprintId) : null;

        if (!$eventCarbonFootprint instanceof EventCarbonFootprint) {
            return;
        }

        $subject = (string) new TranslatableMessage('Informe de huella de carbono disponible');
        $params = [
            'user' => $user,
            'report' => $report,
            'eventId' => (string) $eventCarbonFootprint->eventId(),
        ];

        $this->notifier->notify(new SystemNotification(
            recipientId: $userId,
            channel: NotificationChannel::Internal,
            subject: $subject,
            template: '@carbon_footprint/notification/report-generated.notification.html.twig',
            params: $params,
            accountId: $user->accountId(),
        ));

        $this->notifier->notify(new SystemNotification(
            recipientId: $userId,
            channel: NotificationChannel::Email,
            subject: $subject,
            template: '@carbon_footprint/notification/report-generated.email.html.twig',
            params: $params,
            accountId: $user->accountId(),
        ));
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof EventCarbonFootprintReportGenerated;
    }
}
