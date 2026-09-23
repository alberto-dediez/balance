<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Notification;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubmittedForReview;
use App\Notifications\Application\Notification\Notify\SystemNotification;
use App\Notifications\Application\Notification\Notify\SystemNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserFilter;
use App\Security\Domain\Model\User\UserRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;
use Symfony\Component\Translation\TranslatableMessage;

class CarbonFootprintReportSubmittedForReviewNotifier implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private SystemNotifier $notifier,
        private CarbonFootprintReportRepository $reportRepository,
        private AccountRepository $accountRepository,
        private UserRepository $userRepository,
    ) {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$event instanceof CarbonFootprintReportSubmittedForReview) {
            throw new \RuntimeException('Incorrect event!');
        }

        $report = $this->reportRepository->find($event->carbonFootprintReportId());

        if (!$report instanceof CarbonFootprintReport) {
            return;
        }

        $accountId = $report->accountId();
        $account = $accountId instanceof AccountId ? $this->accountRepository->find($accountId) : null;

        $subject = (string) new TranslatableMessage('Informe de huella pendiente de revisión');
        $params = ['report' => $report, 'accountName' => $account?->name() ?? '---'];

        foreach ($this->administrators() as $admin) {
            foreach ([[NotificationChannel::Internal, 'notification'], [NotificationChannel::Email, 'email']] as [$channel, $suffix]) {
                $this->notifier->notify(new SystemNotification(
                    recipientId: $admin->id(),
                    channel: $channel,
                    subject: $subject,
                    template: sprintf('@carbon_footprint/notification/report-submitted-for-review.%s.html.twig', $suffix),
                    params: $params,
                    accountId: $admin->accountId(),
                ));
            }
        }
    }

    /** @return list<User> */
    private function administrators(): array
    {
        $administrators = [];

        foreach ([Role::Admin, Role::SuperAdmin] as $role) {
            foreach ($this->userRepository->findByFilter(new UserFilter(role: $role, excludeRole: null, limit: 0)) as $user) {
                $administrators[(string) $user->id()] = $user;
            }
        }

        return array_values($administrators);
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof CarbonFootprintReportSubmittedForReview;
    }
}
