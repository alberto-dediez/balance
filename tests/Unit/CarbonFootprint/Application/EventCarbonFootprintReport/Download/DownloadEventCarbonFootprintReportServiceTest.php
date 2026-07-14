<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EventCarbonFootprintReport\Download;

use App\CarbonFootprint\Application\EventCarbonFootprintReport\Download\DownloadEventCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\EventCarbonFootprintReport\Download\DownloadEventCarbonFootprintReportService;
use App\CarbonFootprint\Application\EventCarbonFootprintReport\EventCarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Infrastructure\Storage\InMemoryEventCarbonFootprintReportStorage;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class DownloadEventCarbonFootprintReportServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testDownloadsGeneratedReport(): void
    {
        $reportRepository = EventCarbonFootprintReportRepositoryMother::inMemory();
        $storage = new InMemoryEventCarbonFootprintReportStorage();

        $report = EventCarbonFootprintReportMother::pending(accountId: 'account-id');
        $path = $storage->store($report->id(), $report->accountId(), Locale::Spanish, $this->tempFileWithContent('pdf-bytes'));
        $report->translate($path, Locale::Spanish);
        $report->generate();
        $reportRepository->add($report);

        $sut = new DownloadEventCarbonFootprintReportService($reportRepository, new EventCarbonFootprintReportAuthorizer(), $storage);

        $response = ($sut)(
            new DownloadEventCarbonFootprintReportRequest($report->id()),
            $this->ownerUser(),
        );

        $this->assertSame('pdf-bytes', stream_get_contents($response->stream));
    }

    public function testThrowsWhenReportIsNotGenerated(): void
    {
        $reportRepository = EventCarbonFootprintReportRepositoryMother::inMemory();
        $storage = new InMemoryEventCarbonFootprintReportStorage();

        $report = EventCarbonFootprintReportMother::pending(accountId: 'account-id');
        $reportRepository->add($report);

        $sut = new DownloadEventCarbonFootprintReportService($reportRepository, new EventCarbonFootprintReportAuthorizer(), $storage);

        $this->expectException(\InvalidArgumentException::class);
        ($sut)(new DownloadEventCarbonFootprintReportRequest($report->id()), $this->ownerUser());
    }

    public function testThrowsWhenNoTranslationIsAvailable(): void
    {
        $reportRepository = EventCarbonFootprintReportRepositoryMother::inMemory();
        $storage = new InMemoryEventCarbonFootprintReportStorage();

        $report = EventCarbonFootprintReportMother::pending(accountId: 'account-id');
        $path = $storage->store($report->id(), $report->accountId(), Locale::Turkish, $this->tempFileWithContent('pdf-bytes'));
        $report->translate($path, Locale::Turkish);
        $report->generate();
        $reportRepository->add($report);

        $sut = new DownloadEventCarbonFootprintReportService($reportRepository, new EventCarbonFootprintReportAuthorizer(), $storage);

        $this->expectException(\InvalidArgumentException::class);
        ($sut)(new DownloadEventCarbonFootprintReportRequest($report->id()), $this->ownerUser());
    }

    public function testUnauthorizedAccessThrows(): void
    {
        $reportRepository = EventCarbonFootprintReportRepositoryMother::inMemory();
        $storage = new InMemoryEventCarbonFootprintReportStorage();

        $report = EventCarbonFootprintReportMother::generated(accountId: 'account-id');
        $reportRepository->add($report);

        $sut = new DownloadEventCarbonFootprintReportService($reportRepository, new EventCarbonFootprintReportAuthorizer(), $storage);

        $this->expectException(UnauthorizedActionException::class);
        ($sut)(
            new DownloadEventCarbonFootprintReportRequest($report->id()),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    private function tempFileWithContent(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ecf-report-test-');
        assert(false !== $path);
        file_put_contents($path, $content);

        return $path;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
