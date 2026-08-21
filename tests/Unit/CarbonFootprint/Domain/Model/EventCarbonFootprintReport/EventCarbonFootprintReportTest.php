<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportFailed;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportGenerated;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportId;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRegistered;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportReissued;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRemoved;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportStatus;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class EventCarbonFootprintReportTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testRegisterPublishesEventAndSetsPendingStatus(): void
    {
        $report = new EventCarbonFootprintReport(id: EventCarbonFootprintReportId::generate());
        $report->register(EventCarbonFootprintId::generate(), new AccountId('account-id'), UserId::generate());

        $this->assertSame(EventCarbonFootprintReportStatus::Pending, $report->status());
        $this->assertInstanceOf(EventCarbonFootprintReportRegistered::class, $this->spySubscriber->lastEvent());
    }

    public function testGeneratePublishesEventAndSetsGeneratedStatus(): void
    {
        $report = EventCarbonFootprintReportMother::pending();

        $report->translate('shared/report-es.pdf', Locale::Spanish);
        $report->translate('shared/report-en.pdf', Locale::English);
        $report->generate();

        $this->assertSame(EventCarbonFootprintReportStatus::Generated, $report->status());
        $this->assertSame('shared/report-es.pdf', $report->filePathFor(Locale::Spanish));
        $this->assertSame('shared/report-en.pdf', $report->filePathFor(Locale::English));
        $this->assertNull($report->errorMessage());
        $this->assertInstanceOf(EventCarbonFootprintReportGenerated::class, $this->spySubscriber->lastEvent());
    }

    public function testFailPublishesEventAndSetsFailedStatus(): void
    {
        $report = EventCarbonFootprintReportMother::pending();

        $report->fail('something went wrong');

        $this->assertSame(EventCarbonFootprintReportStatus::Failed, $report->status());
        $this->assertSame('something went wrong', $report->errorMessage());
        $this->assertInstanceOf(EventCarbonFootprintReportFailed::class, $this->spySubscriber->lastEvent());
    }

    public function testReissuePublishesEventAndReturnsToPendingStatus(): void
    {
        $report = EventCarbonFootprintReportMother::failed();

        $report->reissue();

        $this->assertSame(EventCarbonFootprintReportStatus::Pending, $report->status());
        $this->assertNull($report->errorMessage());
        $this->assertInstanceOf(EventCarbonFootprintReportReissued::class, $this->spySubscriber->lastEvent());
    }

    public function testReissueKeepsTheGeneratedAtOfThePreviousIssueUntilItIsGeneratedAgain(): void
    {
        $report = EventCarbonFootprintReportMother::generated();
        $previousGeneratedAt = $report->generatedAt();

        $report->reissue();

        $this->assertSame($previousGeneratedAt, $report->generatedAt());
        $this->assertSame('shared/report-es.pdf', $report->filePathFor(Locale::Spanish));

        $report->generate();

        $this->assertSame(EventCarbonFootprintReportStatus::Generated, $report->status());
    }

    public function testRemovePublishesEvent(): void
    {
        $report = EventCarbonFootprintReportMother::generated();

        $report->remove();

        $this->assertInstanceOf(EventCarbonFootprintReportRemoved::class, $this->spySubscriber->lastEvent());
    }

    public function testFilePathForReturnsPathMatchingLocale(): void
    {
        $report = EventCarbonFootprintReportMother::generated();

        $this->assertSame('shared/report-es.pdf', $report->filePathFor(Locale::Spanish));
        $this->assertSame('shared/report-en.pdf', $report->filePathFor(Locale::English));
        $this->assertNull($report->filePathFor(Locale::French));
    }

    public function testFilePathForReturnsNullWhenPending(): void
    {
        $report = EventCarbonFootprintReportMother::pending();

        $this->assertNull($report->filePathFor(Locale::Spanish));
    }

    public function testTranslationForCurrentLocaleFallsBackToDefaultLocale(): void
    {
        $report = EventCarbonFootprintReportMother::generated();

        LocaleGetter::setLocale(Locale::French);

        try {
            $translation = $report->currentTranslation();

            $this->assertNotNull($translation);
            $this->assertSame(LocaleGetter::default(), $translation->locale());
            $this->assertSame('shared/report-es.pdf', $translation->filePath());
        } finally {
            LocaleGetter::setLocale(null);
        }
    }
}
