<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Notification;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportId;
use App\CarbonFootprint\Infrastructure\Notification\EventCarbonFootprintReportOutdatedNotifier;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintReportRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\Notifications\Application\Notification\Notify\SpySystemNotifier;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class EventCarbonFootprintReportOutdatedNotifierTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testNotifiesTheRequesterWhenTheCompanyCompensatesAnAlreadyGeneratedReport(): void
    {
        [$notifier, $projectContributions, $eventId, $userId] = $this->context();

        $contribution = ProjectContributionMother::pending(
            accountId: new AccountId('account-id'),
            originId: $eventId,
            origin: ProjectContributionOrigin::EventCarbonFootprint,
        );
        $projectContributions->add($contribution);

        $contribution->pay();

        $notifications = $notifier->notifications();
        $this->assertCount(2, $notifications);
        $this->assertSame(NotificationChannel::Internal, $notifications[0]->channel);
        $this->assertSame(NotificationChannel::Email, $notifications[1]->channel);
        $this->assertTrue($notifications[0]->recipientId->equals($userId));
    }

    public function testDoesNotNotifyForAttendeeContributions(): void
    {
        [$notifier, $projectContributions, $eventId] = $this->context();

        $contribution = ProjectContributionMother::pending(
            accountId: new AccountId('account-id'),
            originId: $eventId,
            origin: ProjectContributionOrigin::AttendeeSurveyResponse,
        );
        $projectContributions->add($contribution);

        $contribution->pay();

        $this->assertCount(0, $notifier->notifications());
    }

    public function testDoesNotNotifyWhenTheReportIsNotGeneratedYet(): void
    {
        [$notifier, $projectContributions, $eventId] = $this->context(generated: false);

        $contribution = ProjectContributionMother::pending(
            accountId: new AccountId('account-id'),
            originId: $eventId,
            origin: ProjectContributionOrigin::EventCarbonFootprint,
        );
        $projectContributions->add($contribution);

        $contribution->pay();

        $this->assertCount(0, $notifier->notifications());
    }

    /**
     * @return array{0: SpySystemNotifier, 1: \App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryProjectContributionRepository, 2: string, 3: UserId}
     */
    private function context(bool $generated = true): array
    {
        $eventId = (string) EventId::generate();

        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount($eventId, 'account-id');
        $eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();
        $eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $userId = UserId::generate();
        $userRepository = new InMemoryUserRepository();
        $userRepository->add(UserMother::fromUsernameAndRoleAndId('owner', Role::Owner, $userId));

        $report = new EventCarbonFootprintReport(id: EventCarbonFootprintReportId::generate());
        $report->register($eventCarbonFootprint->id(), new AccountId('account-id'), $userId);

        if ($generated) {
            $report->translate('account-id/report-es.pdf', Locale::Spanish);
            $report->generate();
        }

        $reportRepository = new InMemoryEventCarbonFootprintReportRepository();
        $reportRepository->add($report);

        $projectContributions = ProjectContributionRepositoryMother::inMemory();
        $notifier = new SpySystemNotifier();

        new EventCarbonFootprintReportOutdatedNotifier(
            $notifier,
            $projectContributions,
            $eventCarbonFootprintRepository,
            $reportRepository,
            $userRepository,
        );

        return [$notifier, $projectContributions, $eventId, $userId];
    }
}
