<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeParticipation\Close;

use App\CarbonFootprint\Application\Activity\Answer\PhaseAnswersSynchronizer;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\Mobility\MobilityFactorBasisProvider;
use App\CarbonFootprint\Domain\Model\Activity\Answer\MobilityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\OvernightStayAnswer;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\MarginOfError;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContribution;
use App\CarbonFootprint\Domain\Model\EmployeeContribution\EmployeeContributionRepository;
use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeContributionsAggregation;
use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeContributionsAggregator;
use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipationRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\SectionAnswer;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\SectionDefinition;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\OvernightStay\CategoryOvernight;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayFilter;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class CloseEmployeeParticipationService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventCarbonFootprintAuthorizer $eventCarbonFootprintAuthorizer,
        private EmployeeParticipationRepository $employeeParticipationRepository,
        private EmployeeContributionRepository $employeeContributionRepository,
        private EmployeeContributionsAggregator $aggregator,
        private MobilityFactorBasisProvider $mobilityFactorBasisProvider,
        private OvernightStayRepository $overnightStayRepository,
        private PhaseAnswersSynchronizer $answersSynchronizer,
    ) {}

    public function __invoke(CloseEmployeeParticipationRequest $request, User $user): CloseEmployeeParticipationResponse
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null == $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->eventCarbonFootprintAuthorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::Edit, $user);

        $employeeParticipation = $this->employeeParticipationRepository->findByEventCarbonFootprintId($request->eventCarbonFootprintId);

        if (null === $employeeParticipation) {
            throw new \InvalidArgumentException('EmployeeParticipation not found for this EventCarbonFootprint');
        }

        $answeredContributions = array_values(array_filter(
            $this->employeeContributionRepository->findByEventCarbonFootprintId($request->eventCarbonFootprintId),
            static fn(EmployeeContribution $contribution) => $contribution->status()->isAnswered(),
        ));

        $answeredCount = count($answeredContributions);
        $totalEmployees = max($employeeParticipation->totalEmployees() ?? $answeredCount, $answeredCount);
        $confidenceLevel = $employeeParticipation->confidenceLevel() ?? ConfidenceLevel::NinetyFive;

        $marginOfError = MarginOfError::calculate($answeredCount, $totalEmployees, $confidenceLevel);

        $employeeParticipation->close($marginOfError);
        $this->employeeParticipationRepository->add($employeeParticipation);

        $overnightStayCatalogMissing = false;

        if ($answeredCount >= 1) {
            $aggregation = $this->aggregator->aggregate($answeredContributions, $totalEmployees, $this->mobilityFactorBasisProvider->factorBasisByMobilityId());

            foreach ([Phase::Assembly, Phase::Celebration, Phase::Breakdown] as $phase) {
                $overnightStayCatalogMissing = $this->syncPhase($eventCarbonFootprint, $phase, $aggregation, $user) || $overnightStayCatalogMissing;
            }

            $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);
        }

        return new CloseEmployeeParticipationResponse($employeeParticipation, $overnightStayCatalogMissing);
    }

    private function syncPhase(EventCarbonFootprint $eventCarbonFootprint, Phase $phase, EmployeeContributionsAggregation $aggregation, User $user): bool
    {
        $overnightStayCatalogMissing = false;

        $mobilityAnswers = [];
        foreach ($aggregation->kmByMobilityId($phase) as $mobilityId => $km) {
            if ($km <= 0.0) {
                continue;
            }

            $mobilityAnswers[] = new MobilityAnswer(
                mobilityId: new MobilityId($mobilityId),
                quantity: new Quantity($km, Unit::Kilometer),
            );
        }

        $overnightStayAnswers = [];
        $hotelNights = $aggregation->hotelNights($phase);
        if ($hotelNights > 0.0) {
            $overnightStays = $this->overnightStayRepository->findByFilter(
                new OvernightStayFilter(category: CategoryOvernight::OrganizingTeam, limit: 1),
            );
            $overnightStay = $overnightStays[0] ?? null;

            if (null !== $overnightStay) {
                $overnightStayAnswers[] = new OvernightStayAnswer(
                    overnightStayId: $overnightStay->id(),
                    quantity: new Quantity($hotelNights, Unit::Pernoctacion),
                );
            } else {
                $overnightStayCatalogMissing = true;
            }
        }

        $sectionAnswers = [
            new SectionAnswer($this->mobilitiesSection($phase), $mobilityAnswers),
            new SectionAnswer($this->overnightStaysSection($phase), $overnightStayAnswers),
        ];

        $this->answersSynchronizer->sync(
            $eventCarbonFootprint,
            $phase,
            $sectionAnswers,
            $user,
            [$this->mobilitiesSection($phase), $this->overnightStaysSection($phase)],
            null,
            true,
        );

        return $overnightStayCatalogMissing;
    }

    private function mobilitiesSection(Phase $phase): SectionDefinition
    {
        return $this->section($phase, 'mobilities');
    }

    private function overnightStaysSection(Phase $phase): SectionDefinition
    {
        return $this->section($phase, 'overnightStays');
    }

    private function section(Phase $phase, string $key): SectionDefinition
    {
        foreach ($phase->sections() as $section) {
            if ($section->key === $key) {
                return $section;
            }
        }

        throw new \InvalidArgumentException(sprintf('Section "%s" not found in %s phase.', $key, $phase->value));
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof CloseEmployeeParticipationRequest;
    }
}
