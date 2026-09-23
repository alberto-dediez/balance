<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Notification;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Infrastructure\Notification\CarbonFootprintReportTextsDraftedNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTextsMother;
use PHPUnit\Framework\TestCase;

class CarbonFootprintReportTextsDraftedNotifierTest extends TestCase
{
    use NotifierContextTrait;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->initNotifierContext();

        new CarbonFootprintReportTextsDraftedNotifier($this->notifier, $this->reportRepository, $this->userRepository);
    }

    public function testNotifiesTheRequesterWhenTheTextsAreDrafted(): void
    {
        $report = $this->givenReport(CarbonFootprintReportSubjectType::Property);

        $report->draftTexts(CarbonFootprintReportTextsMother::forOrganization());

        $notifications = $this->notifier->notifications();
        $this->assertCount(2, $notifications);
        $this->assertSame(NotificationChannel::Internal, $notifications[0]->channel);
        $this->assertSame(NotificationChannel::Email, $notifications[1]->channel);
        $this->assertTrue($notifications[0]->recipientId->equals($this->requesterId));
        $this->assertSame('Textos del informe de huella listos para revisar', $notifications[0]->subject);
        $this->assertSame('@carbon_footprint/notification/report-texts-drafted.notification.html.twig', $notifications[0]->template);
    }
}
