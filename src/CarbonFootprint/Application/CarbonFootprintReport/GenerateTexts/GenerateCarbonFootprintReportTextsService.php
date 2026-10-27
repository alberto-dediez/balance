<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\GenerateTexts;

use App\CarbonFootprint\Application\CarbonFootprintReport\Data\CarbonFootprintReportDataCollectorRegistry;
use App\CarbonFootprint\Application\CarbonFootprintReport\GeneratePdf\GenerateCarbonFootprintReportPdfRequest;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTextsGenerator;
use App\Security\Application\Account\ReportPermissionsPolicy;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\Priority;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\Locale;

class GenerateCarbonFootprintReportTextsService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private CarbonFootprintReportRepository $reportRepository,
        private CarbonFootprintReportDataCollectorRegistry $collectors,
        private CarbonFootprintReportTextsGenerator $textsGenerator,
        private ReportPermissionsPolicy $reportPermissionsPolicy,
        private string $localeList,
    ) {}

    public function __invoke(GenerateCarbonFootprintReportTextsRequest $request, User $user): void
    {
        $report = $this->reportRepository->find($request->reportId);

        if (null === $report) {
            throw new \RuntimeException('CarbonFootprintReport not found');
        }

        if (!$report->status()->isGeneratingTexts()) {
            return;
        }

        try {
            $this->generate($report, $user);
        } catch (\Throwable $e) {
            if ($report->status()->isInProgress()) {
                $report->fail($e->getMessage());
                $this->reportRepository->add($report);
            }

            throw $e;
        }
    }

    private function defaultLocale(): Locale
    {
        $locales = Locale::listFrom($this->localeList);

        if ([] === $locales) {
            throw new \RuntimeException('No locales configured');
        }

        return $locales[0];
    }

    private function generate(CarbonFootprintReport $report, User $user): void
    {
        $data = $this->collectors->collect($report);
        $texts = $this->textsGenerator->generate($data, $report->baseLocale() ?? $this->defaultLocale());

        if (!$texts->isCompleteFor($report->subjectType())) {
            throw new \RuntimeException('Incomplete report texts');
        }

        $permissions = $this->reportPermissionsPolicy->permissionsFor($report->accountId(), $user);

        if ($permissions->canEditTexts()) {
            $report->draftTexts($texts);
        } elseif ($permissions->autoReview()) {
            $report->approve($report->requestedBy() ?? $user->id(), true, $texts);
        } else {
            $report->submitForReview($texts);
        }

        $this->reportRepository->add($report);

        if ($report->status()->isGeneratingPdf()) {
            CommandBusDispatcher::instance()->dispatch(
                new DeferredCommand(
                    command: new GenerateCarbonFootprintReportPdfRequest($report->id()),
                    priority: Priority::LOW,
                ),
                $user,
            );
        }
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof GenerateCarbonFootprintReportTextsRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof GenerateCarbonFootprintReportTextsRequest;
    }
}
