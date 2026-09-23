<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Notification;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRejected;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\CarbonFootprintReport\CarbonFootprintReportResultsRoute;
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

class CarbonFootprintReportRejectedNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private SystemNotifier $notifier,
        private CarbonFootprintReportRepository $reportRepository,
        private CarbonFootprintReportResultsRoute $resultsRoute,
        private UserRepository $userRepository,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$event instanceof CarbonFootprintReportRejected) {
            throw new \RuntimeException('Incorrect event!');
        }

        $report = $this->reportRepository->find($event->carbonFootprintReportId());

        if (!$report instanceof CarbonFootprintReport) {
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

        $results = $this->resultsRoute->resolve($report);

        $subject = (string) new TranslatableMessage('Informe de huella rechazado');
        $params = [
            'user' => $user,
            'report' => $report,
            'comment' => $event->comment(),
            'resultsRoute' => $results['route'],
            'resultsParams' => $results['params'],
        ];

        foreach ([[NotificationChannel::Internal, 'notification'], [NotificationChannel::Email, 'email']] as [$channel, $suffix]) {
            $this->notifier->notify(new SystemNotification(
                recipientId: $userId,
                channel: $channel,
                subject: $subject,
                template: sprintf('@carbon_footprint/notification/report-rejected.%s.html.twig', $suffix),
                params: $params,
                accountId: $user->accountId(),
            ));
        }
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof CarbonFootprintReportRejected;
    }
}
