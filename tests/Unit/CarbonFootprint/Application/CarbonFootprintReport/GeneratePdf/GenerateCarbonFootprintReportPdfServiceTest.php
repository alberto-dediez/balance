<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\GeneratePdf;

use App\CarbonFootprint\Application\CarbonFootprintReport\Data\CarbonFootprintReportDataCollectorRegistry;
use App\CarbonFootprint\Application\CarbonFootprintReport\GeneratePdf\GenerateCarbonFootprintReportPdfRequest;
use App\CarbonFootprint\Application\CarbonFootprintReport\GeneratePdf\GenerateCarbonFootprintReportPdfService;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportStatus;
use App\CarbonFootprint\Infrastructure\Storage\InMemoryCarbonFootprintReportStorage;
use App\Security\Application\Branding\Resolve\ResolveBrandingService;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Branding\BrandingPalette;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryBrandingRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\Theme\ClassicTheme;
use App\Shared\Infrastructure\Theme\DefaultTheme;
use App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\FailingOnAddCarbonFootprintReportRepository;
use App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\GenerateTexts\FakeCarbonFootprintReportTextsGenerator;
use App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\GenerateTexts\StubCarbonFootprintReportDataCollector;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingMother;
use App\Tests\Unit\Security\Domain\Model\Branding\BrandingRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Theme\ThemeRegistryMother;
use PHPUnit\Framework\TestCase;

class GenerateCarbonFootprintReportPdfServiceTest extends TestCase
{
    private InMemoryBrandingRepository $brandings;
    private InMemoryCarbonFootprintReportStorage $storage;
    private FakeCarbonFootprintReportTextsGenerator $textsGenerator;
    private FakeCarbonFootprintReportPdfGenerator $pdfGenerator;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->brandings = BrandingRepositoryMother::inMemory();
        $this->storage = new InMemoryCarbonFootprintReportStorage();
        $this->textsGenerator = new FakeCarbonFootprintReportTextsGenerator();
        $this->pdfGenerator = new FakeCarbonFootprintReportPdfGenerator();
    }

    public function testGeneratesOneFilePerLocaleAndMarksTheReportGenerated(): void
    {
        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();
        $report = CarbonFootprintReportMother::generatingPdf();
        $reportRepository->add($report);

        ($this->createSUT($reportRepository))(new GenerateCarbonFootprintReportPdfRequest($report->id()), UserMother::systemUser());

        $this->assertSame(CarbonFootprintReportStatus::Generated, $report->status());
        $this->assertNotNull($report->filePathFor(Locale::Spanish));
        $this->assertNotNull($report->filePathFor(Locale::English));
        $this->assertCount(2, $this->storage->paths());
        $this->assertSame(['default', 'default'], $this->pdfGenerator->themeSlugs());
    }

    public function testTranslatesOnlyTheLocalesOtherThanTheBaseOne(): void
    {
        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();
        $report = CarbonFootprintReportMother::generatingPdf();
        $reportRepository->add($report);

        ($this->createSUT($reportRepository))(new GenerateCarbonFootprintReportPdfRequest($report->id()), UserMother::systemUser());

        $this->assertSame([], $this->textsGenerator->generateCalls());
        $this->assertSame([Locale::English], $this->textsGenerator->translateCalls());
        $this->assertSame($report->texts()->sections, $this->pdfGenerator->texts()[0]->sections);
    }

    public function testUsesTheThemeOfTheAccountBranding(): void
    {
        $accountId = new AccountId('classic-account');
        $branding = BrandingMother::forAccount($accountId);
        $branding->changeTheme(ClassicTheme::SLUG);
        $this->brandings->add($branding);

        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();
        $report = CarbonFootprintReportMother::generatingPdf(accountId: $accountId);
        $reportRepository->add($report);

        ($this->createSUT($reportRepository))(new GenerateCarbonFootprintReportPdfRequest($report->id()), UserMother::systemUser());

        $this->assertSame(['classic', 'classic'], $this->pdfGenerator->themeSlugs());
    }

    public function testFailureMarksTheReportFailedAndDeletesTheTemporaryFiles(): void
    {
        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();
        $report = CarbonFootprintReportMother::generatingPdf();
        $reportRepository->add($report);
        $this->pdfGenerator = new FakeCarbonFootprintReportPdfGenerator(failOn: Locale::English);

        $this->expectException(\RuntimeException::class);

        try {
            ($this->createSUT($reportRepository))(new GenerateCarbonFootprintReportPdfRequest($report->id()), UserMother::systemUser());
        } finally {
            $this->assertSame(CarbonFootprintReportStatus::Failed, $report->status());
            $this->assertSame('rendering failure', $report->errorMessage());
            $this->assertCount(0, $this->storage->paths());

            foreach ($this->pdfGenerator->generatedPaths() as $path) {
                $this->assertFileDoesNotExist($path);
            }
        }
    }

    public function testAFailureAfterGeneratingIsRethrownWithoutMarkingTheReportAsFailed(): void
    {
        $reportRepository = new FailingOnAddCarbonFootprintReportRepository();
        $report = CarbonFootprintReportMother::generatingPdf();
        $reportRepository->add($report);
        $reportRepository->failOnNextAdd();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('persistence failure');

        try {
            ($this->createSUT($reportRepository))(new GenerateCarbonFootprintReportPdfRequest($report->id()), UserMother::systemUser());
        } finally {
            $this->assertSame(CarbonFootprintReportStatus::Generated, $report->status());
            $this->assertNull($report->errorMessage());
        }
    }

    public function testDoesNothingWhenTheReportIsNotGeneratingPdf(): void
    {
        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();
        $report = CarbonFootprintReportMother::draft();
        $reportRepository->add($report);

        ($this->createSUT($reportRepository))(new GenerateCarbonFootprintReportPdfRequest($report->id()), UserMother::systemUser());

        $this->assertSame([], $this->pdfGenerator->themeSlugs());
        $this->assertSame(CarbonFootprintReportStatus::Draft, $report->status());
    }

    public function testDeletesTheTemporaryFilesAfterStoringThem(): void
    {
        $reportRepository = CarbonFootprintReportRepositoryMother::inMemory();
        $report = CarbonFootprintReportMother::generatingPdf();
        $reportRepository->add($report);

        ($this->createSUT($reportRepository))(new GenerateCarbonFootprintReportPdfRequest($report->id()), UserMother::systemUser());

        foreach ($this->pdfGenerator->generatedPaths() as $path) {
            $this->assertFileDoesNotExist($path);
        }
    }

    private function createSUT(CarbonFootprintReportRepository $reportRepository): GenerateCarbonFootprintReportPdfService
    {
        return new GenerateCarbonFootprintReportPdfService(
            $reportRepository,
            new CarbonFootprintReportDataCollectorRegistry([new StubCarbonFootprintReportDataCollector()]),
            $this->textsGenerator,
            new FakeCarbonFootprintReportChartsGenerator(),
            $this->pdfGenerator,
            $this->storage,
            new ResolveBrandingService($this->brandings, ThemeRegistryMother::withThemes([new ClassicTheme(new DefaultTheme())]), new BrandingPalette()),
            'es|en',
        );
    }
}
