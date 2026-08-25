<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurvey\Close;

use App\CarbonFootprint\Application\Activity\Answer\PhaseAnswersSynchronizer;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\Mobility\MobilityFactorBasisProvider;
use App\CarbonFootprint\Domain\Model\Activity\Answer\MobilityAnswer;
use App\CarbonFootprint\Domain\Model\Activity\Answer\OvernightStayAnswer;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepository;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyResponsesAggregator;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\MarginOfError;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;
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

class CloseAttendeeSurveyService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        private EventCarbonFootprintAuthorizer $eventCarbonFootprintAuthorizer,
        private AttendeeSurveyRepository $attendeeSurveyRepository,
        private AttendeeSurveyResponseRepository $attendeeSurveyResponseRepository,
        private AttendeeSurveyResponsesAggregator $aggregator,
        private OvernightStayRepository $overnightStayRepository,
        private PhaseAnswersSynchronizer $answersSynchronizer,
        private MobilityFactorBasisProvider $mobilityFactorBasisProvider,
    ) {}

    public function __invoke(CloseAttendeeSurveyRequest $request, User $user): CloseAttendeeSurveyResponse
    {
        $eventCarbonFootprint = $this->eventCarbonFootprintRepository->find($request->eventCarbonFootprintId);

        if (null == $eventCarbonFootprint) {
            throw new \InvalidArgumentException('EventCarbonFootprint not found');
        }

        $this->eventCarbonFootprintAuthorizer->denyUnlessCanDoThat($eventCarbonFootprint, SecurityAction::Edit, $user);

        $eventId = $eventCarbonFootprint->eventId();
        $attendeeSurvey = null !== $eventId ? $this->attendeeSurveyRepository->findByEventId($eventId) : null;

        if (null === $attendeeSurvey) {
            throw new \InvalidArgumentException('AttendeeSurvey not found for this EventCarbonFootprint');
        }

        $responses = $this->attendeeSurveyResponseRepository->findByAttendeeSurveyId($attendeeSurvey->id());
        $responsesCount = count($responses);

        $marginOfError = MarginOfError::calculate($responsesCount, $request->realAttendees, $request->confidenceLevel);

        $attendeeSurvey->close($request->realAttendees, $request->confidenceLevel, $marginOfError);
        $this->attendeeSurveyRepository->add($attendeeSurvey);

        $overnightStayCatalogMissing = false;

        if ($responsesCount >= 1) {
            $aggregation = $this->aggregator->aggregate($responses, $request->realAttendees, $this->mobilityFactorBasisProvider->factorBasisByMobilityId());

            $sectionAnswers = [];

            $mobilityAnswers = [];
            foreach ($aggregation->kmByMobilityId as $mobilityId => $km) {
                if ($km <= 0.0) {
                    continue;
                }

                $mobilityAnswers[] = new MobilityAnswer(
                    mobilityId: new MobilityId($mobilityId),
                    quantity: new Quantity($km, Unit::Kilometer),
                );
            }

            $sectionAnswers[] = new SectionAnswer($this->attendeesMobilitiesSection(), $mobilityAnswers);

            $overnightStayAnswers = [];
            if ($aggregation->overnightStays > 0.0) {
                $overnightStays = $this->overnightStayRepository->findByFilter(
                    new OvernightStayFilter(category: CategoryOvernight::Attendees, limit: 1),
                );
                $overnightStay = $overnightStays[0] ?? null;

                if (null !== $overnightStay) {
                    $overnightStayAnswers[] = new OvernightStayAnswer(
                        overnightStayId: $overnightStay->id(),
                        quantity: new Quantity($aggregation->overnightStays, Unit::Pernoctacion),
                    );
                } else {
                    $overnightStayCatalogMissing = true;
                }
            }

            $sectionAnswers[] = new SectionAnswer($this->attendeesOvernightStaysSection(), $overnightStayAnswers);

            $this->answersSynchronizer->sync(
                $eventCarbonFootprint,
                Phase::Celebration,
                $sectionAnswers,
                $user,
                [$this->attendeesMobilitiesSection(), $this->attendeesOvernightStaysSection()],
            );
        }

        $eventCarbonFootprint->answerAttendees();
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        return new CloseAttendeeSurveyResponse($attendeeSurvey, $overnightStayCatalogMissing);
    }

    private function attendeesMobilitiesSection(): SectionDefinition
    {
        return $this->section('attendeesMobilities');
    }

    private function attendeesOvernightStaysSection(): SectionDefinition
    {
        return $this->section('attendeesOvernightStays');
    }

    private function section(string $key): SectionDefinition
    {
        foreach (Phase::Celebration->sections() as $section) {
            if ($section->key === $key) {
                return $section;
            }
        }

        throw new \InvalidArgumentException(sprintf('Section "%s" not found in Celebration phase.', $key));
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof CloseAttendeeSurveyRequest;
    }
}
