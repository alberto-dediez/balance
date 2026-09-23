<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Notification;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Infrastructure\Notification\CarbonFootprintReportGeneratedNotifier;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use PHPUnit\Framework\TestCase;

class CarbonFootprintReportGeneratedNotifierTest extends TestCase
{
    use NotifierContextTrait;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->initNotifierContext();

        new CarbonFootprintReportGeneratedNotifier($this->notifier, $this->reportRepository, $this->resultsRoute(), $this->userRepository);
    }

    /** @return iterable<string, array{0: CarbonFootprintReportSubjectType, 1: string, 2: string}> */
    public static function subjects(): iterable
    {
        yield 'event' => [CarbonFootprintReportSubjectType::Event, 'events_carbon_footprint_results', 'eventId'];
        yield 'property' => [CarbonFootprintReportSubjectType::Property, 'property_carbon_footprints_results', 'propertyCarbonFootprintId'];
        yield 'company' => [CarbonFootprintReportSubjectType::Company, 'company_carbon_footprints_results', 'companyCarbonFootprintId'];
    }

    /** @dataProvider subjects */
    public function testNotifiesTheRequesterWithTheResultsRouteOfTheSubject(CarbonFootprintReportSubjectType $subjectType, string $route, string $param): void
    {
        $report = $this->givenReport($subjectType, 'generatingPdf');

        $report->translate('account-id/report-es.pdf', Locale::Spanish);
        $report->generate();

        $notifications = $this->notifier->notifications();
        $this->assertCount(2, $notifications);
        $this->assertSame(NotificationChannel::Internal, $notifications[0]->channel);
        $this->assertSame(NotificationChannel::Email, $notifications[1]->channel);
        $this->assertTrue($notifications[0]->recipientId->equals($this->requesterId));
        $this->assertSame($route, $notifications[0]->params['resultsRoute']);
        $this->assertArrayHasKey($param, $notifications[0]->params['resultsParams']);
        $this->assertSame(CarbonFootprintReportSubjectType::Event === $subjectType ? 'event-1' : $report->carbonFootprintId(), $notifications[0]->params['resultsParams'][$param]);
    }
}
