<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Notification;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Infrastructure\Notification\CarbonFootprintReportRejectedNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use PHPUnit\Framework\TestCase;

class CarbonFootprintReportRejectedNotifierTest extends TestCase
{
    use NotifierContextTrait;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->initNotifierContext();

        new CarbonFootprintReportRejectedNotifier($this->notifier, $this->reportRepository, $this->resultsRoute(), $this->userRepository);
    }

    public function testNotifiesTheRequesterWithTheCommentAndTheResultsRoute(): void
    {
        $report = $this->givenReport(CarbonFootprintReportSubjectType::Company, 'pendingReview');

        $report->reject(UserId::generate(), 'Revisa el alcance 2');

        $notifications = $this->notifier->notifications();
        $this->assertCount(2, $notifications);
        $this->assertSame(NotificationChannel::Internal, $notifications[0]->channel);
        $this->assertSame(NotificationChannel::Email, $notifications[1]->channel);
        $this->assertTrue($notifications[0]->recipientId->equals($this->requesterId));
        $this->assertSame('Revisa el alcance 2', $notifications[0]->params['comment']);
        $this->assertSame('company_carbon_footprints_results', $notifications[0]->params['resultsRoute']);
    }
}
