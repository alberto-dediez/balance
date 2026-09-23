<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Notification;

use App\Balance\Domain\Model\ProjectContribution\ProjectContribution;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionPaid;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Event\EventId;
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

class EventCarbonFootprintReportOutdatedNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private SystemNotifier $notifier,
        private ProjectContributionRepository $projectContributionRepository,
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private CarbonFootprintReportRepository $reportRepository,
        private UserRepository $userRepository,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$event instanceof ProjectContributionPaid) {
            throw new \RuntimeException('Incorrect event!');
        }

        $projectContribution = $this->projectContributionRepository->find($event->projectContributionId());

        if (!$projectContribution instanceof ProjectContribution || ProjectContributionOrigin::EventCarbonFootprint !== $projectContribution->origin()) {
            return;
        }

        $originId = $projectContribution->originId();

        if (null === $originId) {
            return;
        }

        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->findByEventId(new EventId($originId));

        if (!$eventCarbonFootprint instanceof EventCarbonFootprint) {
            return;
        }

        $report = $this->reportRepository->findBySubject(CarbonFootprintReportSubjectType::Event, (string) $eventCarbonFootprint->id());

        if (!$report instanceof CarbonFootprintReport || !$report->status()->isGenerated()) {
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

        $subject = (string) new TranslatableMessage('Puedes reemitir el informe de huella de carbono');
        $params = [
            'user' => $user,
            'report' => $report,
            'eventId' => (string) $eventCarbonFootprint->eventId(),
        ];

        $this->notifier->notify(new SystemNotification(
            recipientId: $userId,
            channel: NotificationChannel::Internal,
            subject: $subject,
            template: '@carbon_footprint/notification/report-outdated.notification.html.twig',
            params: $params,
            accountId: $user->accountId(),
        ));

        $this->notifier->notify(new SystemNotification(
            recipientId: $userId,
            channel: NotificationChannel::Email,
            subject: $subject,
            template: '@carbon_footprint/notification/report-outdated.email.html.twig',
            params: $params,
            accountId: $user->accountId(),
        ));
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof ProjectContributionPaid;
    }
}
