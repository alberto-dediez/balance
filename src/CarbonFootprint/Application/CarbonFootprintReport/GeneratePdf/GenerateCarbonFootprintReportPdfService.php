<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\GeneratePdf;

use App\CarbonFootprint\Application\CarbonFootprintReport\Data\CarbonFootprintReportDataCollectorRegistry;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportChartsGenerator;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportPdfGenerator;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportStorage;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTextsGenerator;
use App\Security\Application\Branding\Resolve\BrandingResolver;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\Locale;

class GenerateCarbonFootprintReportPdfService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private CarbonFootprintReportRepository $reportRepository,
        private CarbonFootprintReportDataCollectorRegistry $collectors,
        private CarbonFootprintReportTextsGenerator $textsGenerator,
        private CarbonFootprintReportChartsGenerator $chartsGenerator,
        private CarbonFootprintReportPdfGenerator $pdfGenerator,
        private CarbonFootprintReportStorage $storage,
        private BrandingResolver $brandingResolver,
        private string $localeList,
    ) {}

    public function __invoke(GenerateCarbonFootprintReportPdfRequest $request, User $user): void
    {
        $report = $this->reportRepository->find($request->reportId);

        if (null === $report) {
            throw new \RuntimeException('CarbonFootprintReport not found');
        }

        if (!$report->status()->isGeneratingPdf()) {
            return;
        }

        try {
            $this->generate($report);
        } catch (\Throwable $e) {
            if ($report->status()->isInProgress()) {
                $report->fail($e->getMessage());
                $this->reportRepository->add($report);
            }

            throw $e;
        }
    }

    private function generate(CarbonFootprintReport $report): void
    {
        $data = $this->collectors->collect($report);
        $themeSlug = $this->brandingResolver->resolve($report->accountId())->themeSlug;

        $locales = Locale::listFrom($this->localeList);

        if ([] === $locales) {
            throw new \RuntimeException('No locales configured');
        }

        $baseLocale = $report->baseLocale() ?? $locales[0];
        $baseTexts = $report->texts();

        /** @var list<array{0: Locale, 1: string}> $generatedFiles */
        $generatedFiles = [];

        try {
            foreach ($locales as $locale) {
                $texts = $locale !== $baseLocale
                    ? $this->textsGenerator->translate($baseTexts, $baseLocale, $locale)
                    : $baseTexts;

                $charts = $this->chartsGenerator->generate($data, $locale);

                $generatedFiles[] = [$locale, $this->pdfGenerator->generate($data, $texts, $charts, $locale, $themeSlug)];
            }

            foreach ($generatedFiles as [$locale, $localPath]) {
                $report->translate($this->storage->store($report->id(), $report->accountId(), $locale, $localPath), $locale);
            }

            $report->generate();
            $this->reportRepository->add($report);
        } finally {
            foreach ($generatedFiles as [, $localPath]) {
                @unlink($localPath);
            }
        }
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof GenerateCarbonFootprintReportPdfRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof GenerateCarbonFootprintReportPdfRequest;
    }
}
