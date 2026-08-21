<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EventCarbonFootprintReport\Generate;

use App\CarbonFootprint\Application\EventCarbonFootprint\GetResults\GetResultsRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetResults\GetResultsService;
use App\CarbonFootprint\Domain\Model\Activity\ActivityRepository;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportActivityLine;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportChartsGenerator;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportExclusionLine;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportPdfGenerator;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportStorage;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportTextsGenerator;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\Locale;

class GenerateEventCarbonFootprintReportService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private EventCarbonFootprintReportRepository $reportRepository,
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventRepository $eventRepository,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
        private ActivityRepository $activityRepository,
        private GetResultsService $getResultsService,
        private ActivityItemNameResolver $itemNameResolver,
        private EventCompensationCollector $compensationCollector,
        private EventCarbonFootprintReportTextsGenerator $textsGenerator,
        private EventCarbonFootprintReportChartsGenerator $chartsGenerator,
        private EventCarbonFootprintReportPdfGenerator $pdfGenerator,
        private EventCarbonFootprintReportStorage $storage,
        private string $localeList,
    ) {}

    public function __invoke(GenerateEventCarbonFootprintReportRequest $request, User $user): void
    {
        $report = $this->reportRepository->find($request->reportId);

        if (null === $report) {
            throw new \RuntimeException('EventCarbonFootprintReport not found');
        }

        if ($report->status()->isGenerated()) {
            return;
        }

        try {
            $this->generate($report, $user);
        } catch (\Throwable $e) {
            $report->fail($e->getMessage());
            $this->reportRepository->add($report);

            throw $e;
        }
    }

    private function generate(EventCarbonFootprintReport $report, User $user): void
    {
        $eventCarbonFootprintId = $report->eventCarbonFootprintId();
        $eventCarbonFootprint = null !== $eventCarbonFootprintId ? $this->eventCarbonFootprintRepository->find($eventCarbonFootprintId) : null;

        if (null === $eventCarbonFootprint) {
            throw new \RuntimeException('EventCarbonFootprint not found');
        }

        $eventId = $eventCarbonFootprint->eventId();
        $event = null !== $eventId ? $this->eventRepository->find($eventId) : null;

        if (null === $event) {
            throw new \RuntimeException('Event not found');
        }

        $emissionFactorGroupId = $eventCarbonFootprint->emissionFactorGroupId();
        $emissionFactorGroup = null !== $emissionFactorGroupId ? $this->emissionFactorGroupRepository->find($emissionFactorGroupId) : null;

        if (null === $emissionFactorGroup) {
            throw new \RuntimeException('EmissionFactorGroup not found');
        }

        $results = $this->getResultsService->__invoke(new GetResultsRequest($eventCarbonFootprint->id()), $user);

        $year = $eventCarbonFootprint->calculationYear();

        $activityLines = [];
        foreach ($eventCarbonFootprint->activities()->toArray() as $footprintActivity) {
            $activity = $this->activityRepository->findById($footprintActivity->activityId());

            if (null === $activity) {
                continue;
            }

            $activityLines[] = new EventCarbonFootprintReportActivityLine(
                itemName: $this->itemNameResolver->resolve($activity, $year, $emissionFactorGroupId),
                activityType: ActivityType::fromActivity($activity),
                phase: $footprintActivity->phase(),
                source: $footprintActivity->source(),
                quantity: $activity->quantity()->value(),
                unit: $activity->quantity()->unit(),
                factorCo2eq: $activity->emissionFactor()->co2eq(),
                factorUnit: $activity->emissionFactor()->unit(),
                factorYear: $activity->emissionFactor()->year(),
                factorSource: $activity->emissionFactor()->source(),
                emissionsCo2eq: $activity->emissions()->co2eq(),
            );
        }

        $exclusionLines = [];
        foreach ($eventCarbonFootprint->exclusions()->toArray() as $exclusion) {
            $exclusionLines[] = new EventCarbonFootprintReportExclusionLine(
                phase: $exclusion->phase(),
                source: $exclusion->source(),
                reason: $exclusion->reason(),
                comment: $exclusion->comment(),
            );
        }

        $data = new EventCarbonFootprintReportData(
            eventName: $event->name(),
            sector: $event->sector(),
            typology: $event->typology(),
            size: $event->size(),
            estimatedAttendees: $event->estimatedAttendees(),
            assembly: $event->assembly(),
            celebration: $event->celebration(),
            breakdown: $event->breakdown(),
            emissionFactorGroupName: $emissionFactorGroup->name(),
            calculationYear: $year,
            totalEmissions: $results->totalEmissions,
            emissionsPerAttendee: $results->emissionsPerAttendee,
            emissionsBySource: $results->emissionsBySource,
            emissionsByPhase: $results->emissionsByPhase,
            emissionsByPhaseAndSource: $results->emissionsByPhaseAndSource,
            activityLines: $activityLines,
            exclusionLines: $exclusionLines,
            compensation: $this->compensationCollector->__invoke($event->id(), $results->totalEmissions),
        );

        $locales = Locale::listFrom($this->localeList);

        if ([] === $locales) {
            throw new \RuntimeException('No locales configured');
        }

        $baseLocale = $locales[0];

        /** @var list<array{0: Locale, 1: string}> $generatedFiles */
        $generatedFiles = [];

        try {
            $baseTexts = $this->textsGenerator->generate($data, $baseLocale);

            foreach ($locales as $locale) {
                $texts = $locale !== $baseLocale
                    ? $this->textsGenerator->translate($baseTexts, $baseLocale, $locale)
                    : $baseTexts;

                $charts = $this->chartsGenerator->generate($data, $locale);

                $generatedFiles[] = [$locale, $this->pdfGenerator->generate($data, $texts, $charts, $locale)];
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
        assert($command instanceof GenerateEventCarbonFootprintReportRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof GenerateEventCarbonFootprintReportRequest;
    }
}
