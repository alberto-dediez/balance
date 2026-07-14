<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\Locale\Locale;

class EventCarbonFootprintReportMother
{
    public static function pending(?string $eventCarbonFootprintId = null, ?string $accountId = null): EventCarbonFootprintReport
    {
        $report = new EventCarbonFootprintReport(id: EventCarbonFootprintReportId::generate());
        $report->register(
            new EventCarbonFootprintId($eventCarbonFootprintId ?? (string) EventCarbonFootprintId::generate()),
            new AccountId($accountId ?? 'account-id'),
            UserId::generate(),
        );

        return $report;
    }

    public static function generated(?string $eventCarbonFootprintId = null, ?string $accountId = null): EventCarbonFootprintReport
    {
        $report = self::pending($eventCarbonFootprintId, $accountId);
        $report->translate('shared/report-es.pdf', Locale::Spanish);
        $report->translate('shared/report-en.pdf', Locale::English);
        $report->generate();

        return $report;
    }

    public static function failed(?string $eventCarbonFootprintId = null, ?string $accountId = null): EventCarbonFootprintReport
    {
        $report = self::pending($eventCarbonFootprintId, $accountId);
        $report->fail('boom');

        return $report;
    }

    public static function random(): EventCarbonFootprintReport
    {
        return self::pending();
    }
}
