<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportId;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTexts;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Model\Locale\Locale;

class CarbonFootprintReportMother
{
    public static function registeredFor(
        CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Event,
        ?string $carbonFootprintId = null,
        ?AccountId $accountId = null,
        ?UserId $requestedBy = null,
        ?CarbonFootprintReportId $id = null,
    ): CarbonFootprintReport {
        $report = new CarbonFootprintReport(id: $id ?? CarbonFootprintReportId::generate());
        $report->register(
            $subjectType,
            $carbonFootprintId ?? (string) EventCarbonFootprintId::generate(),
            $accountId ?? new AccountId('account-id'),
            $requestedBy ?? UserId::generate(),
            Locale::Spanish,
        );

        return $report;
    }

    public static function draft(
        CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Event,
        ?string $carbonFootprintId = null,
        ?AccountId $accountId = null,
        ?UserId $requestedBy = null,
        ?CarbonFootprintReportId $id = null,
    ): CarbonFootprintReport {
        $report = self::registeredFor($subjectType, $carbonFootprintId, $accountId, $requestedBy, $id);
        $report->draftTexts(self::textsFor($subjectType));

        return $report;
    }

    public static function pendingReview(
        CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Event,
        ?string $carbonFootprintId = null,
        ?AccountId $accountId = null,
        ?UserId $requestedBy = null,
        ?CarbonFootprintReportId $id = null,
    ): CarbonFootprintReport {
        $report = self::registeredFor($subjectType, $carbonFootprintId, $accountId, $requestedBy, $id);
        $report->submitForReview(self::textsFor($subjectType));

        return $report;
    }

    public static function generatingPdf(
        CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Event,
        ?string $carbonFootprintId = null,
        ?AccountId $accountId = null,
        ?UserId $requestedBy = null,
        ?CarbonFootprintReportId $id = null,
    ): CarbonFootprintReport {
        $report = self::registeredFor($subjectType, $carbonFootprintId, $accountId, $requestedBy, $id);
        $report->approve($report->requestedBy() ?? UserId::generate(), true, self::textsFor($subjectType));

        return $report;
    }

    public static function generated(
        CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Event,
        ?string $carbonFootprintId = null,
        ?AccountId $accountId = null,
        ?UserId $requestedBy = null,
        ?CarbonFootprintReportId $id = null,
    ): CarbonFootprintReport {
        $report = self::generatingPdf($subjectType, $carbonFootprintId, $accountId, $requestedBy, $id);
        $report->translate('shared/report-es.pdf', Locale::Spanish);
        $report->translate('shared/report-en.pdf', Locale::English);
        $report->generate();

        return $report;
    }

    public static function failedBeforeApproval(
        CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Event,
        ?string $carbonFootprintId = null,
        ?AccountId $accountId = null,
        ?UserId $requestedBy = null,
        ?CarbonFootprintReportId $id = null,
    ): CarbonFootprintReport {
        $report = self::registeredFor($subjectType, $carbonFootprintId, $accountId, $requestedBy, $id);
        $report->fail('boom');

        return $report;
    }

    public static function failedAfterApproval(
        CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Event,
        ?string $carbonFootprintId = null,
        ?AccountId $accountId = null,
        ?UserId $requestedBy = null,
        ?CarbonFootprintReportId $id = null,
    ): CarbonFootprintReport {
        $report = self::generatingPdf($subjectType, $carbonFootprintId, $accountId, $requestedBy, $id);
        $report->fail('pdf boom');

        return $report;
    }

    public static function rejected(
        CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Event,
        ?string $carbonFootprintId = null,
        ?AccountId $accountId = null,
        ?UserId $requestedBy = null,
        string $comment = 'Revisa las cifras del alcance 2',
        ?CarbonFootprintReportId $id = null,
    ): CarbonFootprintReport {
        $report = self::pendingReview($subjectType, $carbonFootprintId, $accountId, $requestedBy, $id);
        $report->reject(UserId::generate(), $comment);

        return $report;
    }

    public static function random(): CarbonFootprintReport
    {
        return self::registeredFor();
    }

    public static function textsFor(CarbonFootprintReportSubjectType $subjectType): CarbonFootprintReportTexts
    {
        return CarbonFootprintReportSubjectType::Event === $subjectType
            ? CarbonFootprintReportTextsMother::forEvent()
            : CarbonFootprintReportTextsMother::forOrganization();
    }
}
