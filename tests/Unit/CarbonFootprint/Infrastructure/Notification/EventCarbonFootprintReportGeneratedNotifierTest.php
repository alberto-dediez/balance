<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Notification;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportId;
use App\CarbonFootprint\Infrastructure\Notification\EventCarbonFootprintReportGeneratedNotifier;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintReportRepository;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepositoryMother;
use App\Tests\Unit\Notifications\Application\Notification\Notify\SpySystemNotifier;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class EventCarbonFootprintReportGeneratedNotifierTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testNotifiesInternalAndEmailToTheRequester(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::registeredEventCarbonFootprint();
        $eventCarbonFootprintRepository = EventCarbonFootprintRepositoryMother::inMemory();
        $eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $userId = UserId::generate();
        $user = UserMother::fromUsernameAndRoleAndId('owner', Role::Owner, $userId);
        $userRepository = new InMemoryUserRepository();
        $userRepository->add($user);

        $report = new EventCarbonFootprintReport(id: EventCarbonFootprintReportId::generate());
        $report->register($eventCarbonFootprint->id(), new AccountId('account-id'), $userId);

        $reportRepository = new InMemoryEventCarbonFootprintReportRepository();
        $reportRepository->add($report);

        $notifier = new SpySystemNotifier();

        new EventCarbonFootprintReportGeneratedNotifier($notifier, $reportRepository, $eventCarbonFootprintRepository, $userRepository);

        $report->generate();
        $reportRepository->add($report);

        $notifications = $notifier->notifications();
        $this->assertCount(2, $notifications);
        $this->assertSame(NotificationChannel::Internal, $notifications[0]->channel);
        $this->assertSame(NotificationChannel::Email, $notifications[1]->channel);
        $this->assertTrue($notifications[0]->recipientId->equals($userId));
    }
}
