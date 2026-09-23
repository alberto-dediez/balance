<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Notification;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Infrastructure\Notification\CarbonFootprintReportSubmittedForReviewNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTextsMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CarbonFootprintReportSubmittedForReviewNotifierTest extends TestCase
{
    use NotifierContextTrait;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->initNotifierContext();
    }

    public function testNotifiesEveryAdminAndSuperAdminOnce(): void
    {
        $adminId = UserId::generate();
        $superAdminId = UserId::generate();
        $this->userRepository->add(UserMother::fromUsernameAndRoleAndId('admin', Role::Admin, $adminId));
        $this->userRepository->add(UserMother::fromUsernameAndRoleAndId('superadmin', Role::SuperAdmin, $superAdminId));

        $accounts = AccountRepositoryMother::inMemory();
        $accounts->add(AccountMother::fromNameAndId('MyCompany', 'account-id'));

        new CarbonFootprintReportSubmittedForReviewNotifier($this->notifier, $this->reportRepository, $accounts, $this->userRepository);

        $report = $this->givenReport(CarbonFootprintReportSubjectType::Property);
        $report->submitForReview(CarbonFootprintReportTextsMother::forOrganization());

        $notifications = $this->notifier->notifications();
        $this->assertCount(4, $notifications);

        $recipients = array_unique(array_map(static fn($notification) => (string) $notification->recipientId, $notifications));
        $this->assertCount(2, $recipients);
        $this->assertContains((string) $adminId, $recipients);
        $this->assertContains((string) $superAdminId, $recipients);
        $this->assertNotContains((string) $this->requesterId, $recipients);
        $this->assertSame([NotificationChannel::Internal, NotificationChannel::Email], [$notifications[0]->channel, $notifications[1]->channel]);
        $this->assertSame('MyCompany', $notifications[0]->params['accountName']);
    }
}
