<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\Download;

use App\CarbonFootprint\Application\CarbonFootprintReport\CarbonFootprintReportAuthorizer;
use App\CarbonFootprint\Application\CarbonFootprintReport\Download\DownloadCarbonFootprintReportRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\Download\DownloadCarbonFootprintReportService;
use App\CarbonFootprint\Infrastructure\Storage\InMemoryCarbonFootprintReportStorage;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class DownloadCarbonFootprintReportServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testDownloadsGeneratedReport(): void
    {
        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();
        $storage = new InMemoryCarbonFootprintReportStorage();

        $report = CarbonFootprintReportMother::generatingPdf(carbonFootprintId: 'footprint-1');
        $path = $storage->store($report->id(), $report->accountId(), Locale::Spanish, $this->tempFileWithContent('pdf-bytes'));
        $report->translate($path, Locale::Spanish);
        $report->generate();
        $reportRepository->add($report);

        $sut = new DownloadCarbonFootprintReportService($reportRepository, new CarbonFootprintReportAuthorizer(), $storage);

        $response = ($sut)(new DownloadCarbonFootprintReportRequest($report->id()), $this->ownerUser());

        $this->assertSame('pdf-bytes', stream_get_contents($response->stream));
        $this->assertSame('informe-huella-carbono-footprint-1-es.pdf', $response->filename);
    }

    public function testAdminCanDownload(): void
    {
        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();
        $storage = new InMemoryCarbonFootprintReportStorage();

        $report = CarbonFootprintReportMother::generatingPdf();
        $report->translate($storage->store($report->id(), $report->accountId(), Locale::Spanish, $this->tempFileWithContent('pdf-bytes')), Locale::Spanish);
        $report->generate();
        $reportRepository->add($report);

        $sut = new DownloadCarbonFootprintReportService($reportRepository, new CarbonFootprintReportAuthorizer(), $storage);

        $response = ($sut)(new DownloadCarbonFootprintReportRequest($report->id()), UserMother::adminUser());

        $this->assertSame('pdf-bytes', stream_get_contents($response->stream));
    }

    public function testThrowsWhenReportIsNotGenerated(): void
    {
        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();
        $report = CarbonFootprintReportMother::draft();
        $reportRepository->add($report);

        $sut = new DownloadCarbonFootprintReportService($reportRepository, new CarbonFootprintReportAuthorizer(), new InMemoryCarbonFootprintReportStorage());

        $this->expectException(\InvalidArgumentException::class);
        ($sut)(new DownloadCarbonFootprintReportRequest($report->id()), $this->ownerUser());
    }

    public function testThrowsWhenNoTranslationIsAvailable(): void
    {
        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();
        $storage = new InMemoryCarbonFootprintReportStorage();

        $report = CarbonFootprintReportMother::generatingPdf();
        $report->translate($storage->store($report->id(), $report->accountId(), Locale::Turkish, $this->tempFileWithContent('pdf-bytes')), Locale::Turkish);
        $report->generate();
        $reportRepository->add($report);

        $sut = new DownloadCarbonFootprintReportService($reportRepository, new CarbonFootprintReportAuthorizer(), $storage);

        $this->expectException(\InvalidArgumentException::class);
        ($sut)(new DownloadCarbonFootprintReportRequest($report->id()), $this->ownerUser());
    }

    public function testUnauthorizedAccessThrows(): void
    {
        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();
        $report = CarbonFootprintReportMother::generated();
        $reportRepository->add($report);

        $sut = new DownloadCarbonFootprintReportService($reportRepository, new CarbonFootprintReportAuthorizer(), new InMemoryCarbonFootprintReportStorage());

        $this->expectException(UnauthorizedActionException::class);
        ($sut)(new DownloadCarbonFootprintReportRequest($report->id()), UserMother::fromUsernameAndRole('user', Role::User));
    }

    private function tempFileWithContent(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cfp-report-test-');
        assert(false !== $path);
        file_put_contents($path, $content);

        return $path;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
