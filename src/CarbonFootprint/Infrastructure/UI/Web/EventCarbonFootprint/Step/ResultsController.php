<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step;

use App\CarbonFootprint\Application\AttendeeSurvey\GetStats\GetAttendeeSurveyStatsRequest;
use App\CarbonFootprint\Application\AttendeeSurvey\GetStats\GetAttendeeSurveyStatsService;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase\GetPhaseRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetPhase\GetPhaseService;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetResults\GetResultsRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetResults\GetResultsService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\GetEventCarbonFootprintFromEventIdTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\AssemblyBreakdownStepView;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\CelebrationStepView;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\EmployeesStepView;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\MaterialsStepView;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\ProvidersStepView;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\ResultsView;
use App\EventOperations\Domain\Model\Event\Event;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/events/{eventId}/carbon-footprint/step/results', name: 'events_carbon_footprint_results')]
class ResultsController extends AbstractController
{
    use GetEventCarbonFootprintFromEventIdTrait;

    public function __invoke(
        string $eventId,
        EventRepository $eventRepository,
        EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        GetResultsService $getResultsService,
        GetPhaseService $getPhase,
        GetAttendeeSurveyStatsService $getAttendeeSurveyStatsService,
        EventCarbonFootprintReportRepository $reportRepository,
        MaterialsStepView $materialsStepView,
        AssemblyBreakdownStepView $assemblyBreakdownStepView,
        CelebrationStepView $celebrationStepView,
        ProvidersStepView $providersStepView,
        EmployeesStepView $employeesStepView,
        ResultsView $resultsView,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $event = $eventRepository->find(new EventId($eventId))
            ?? throw $this->createNotFoundException('Evento no encontrado');

        $eventCarbonFootprint = $this->getEventCarbonFootprintFromEventId($eventId, $eventCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $eventCarbonFootprint);

        if (!$eventCarbonFootprint->status()->isAttendeesAnswered()) {
            return $this->redirectToRoute('events_carbon_footprint_attendees', ['eventId' => $eventId]);
        }

        if (!$eventCarbonFootprint->status()->isCompleted()) {
            return $this->render('@carbon_footprint/event_carbon_footprints/step/results.html.twig', [
                'event' => $event,
                'eventCarbonFootprint' => $eventCarbonFootprint,
                'eventId' => $eventId,
                'results' => null,
            ]);
        }

        $user = $loggedUser->__invoke();

        $step = $request->query->getString('step');

        $phaseByStep = [
            'materials' => Phase::Materials,
            'assembly' => Phase::Assembly,
            'celebration' => Phase::Celebration,
            'breakdown' => Phase::Breakdown,
        ];

        if (isset($phaseByStep[$step])) {
            $phaseResponse = $getPhase(new GetPhaseRequest($eventCarbonFootprint->id(), $phaseByStep[$step]), $user);

            $stepView = match ($step) {
                'materials' => $materialsStepView,
                'celebration' => $celebrationStepView,
                default => $assemblyBreakdownStepView,
            };

            return $this->render(
                sprintf('@carbon_footprint/event_carbon_footprints/step/%s.html.twig', $step),
                $stepView->build($eventCarbonFootprint, $phaseResponse) + ['event' => $event, 'eventId' => $eventId, 'readOnly' => true],
            );
        }

        if ('providers' === $step) {
            return $this->render(
                '@carbon_footprint/event_carbon_footprints/step/providers.html.twig',
                $providersStepView->build($eventCarbonFootprint, $user) + ['event' => $event, 'eventId' => $eventId, 'readOnly' => true],
            );
        }

        if ('employees' === $step) {
            return $this->render(
                '@carbon_footprint/event_carbon_footprints/step/employees.html.twig',
                $employeesStepView->build($eventCarbonFootprint, $user) + ['event' => $event, 'eventId' => $eventId, 'readOnly' => true],
            );
        }

        if ('attendees' === $step) {
            $attendeesPanel = $this->renderAttendeesPanel($event, $eventCarbonFootprint, $getAttendeeSurveyStatsService, $user, $eventId);

            if (null !== $attendeesPanel) {
                return $attendeesPanel;
            }
        }

        $results = $getResultsService(new GetResultsRequest($eventCarbonFootprint->id()), $user);

        $surveyStats = null;
        try {
            $surveyStats = $getAttendeeSurveyStatsService(new GetAttendeeSurveyStatsRequest($eventCarbonFootprint->id()), $user);
        } catch (\InvalidArgumentException) {
        }

        $report = $reportRepository->findByEventCarbonFootprintId($eventCarbonFootprint->id());

        return $this->render('@carbon_footprint/event_carbon_footprints/step/results.html.twig', array_merge(
            $resultsView->build($event, $eventCarbonFootprint, $results, $surveyStats),
            [
                'eventId' => $eventId,
                'report' => $report,
            ],
        ));
    }

    private function renderAttendeesPanel(
        Event $event,
        EventCarbonFootprint $eventCarbonFootprint,
        GetAttendeeSurveyStatsService $getAttendeeSurveyStatsService,
        User $user,
        string $eventId,
    ): ?Response {
        try {
            $stats = $getAttendeeSurveyStatsService(new GetAttendeeSurveyStatsRequest($eventCarbonFootprint->id()), $user);
        } catch (\InvalidArgumentException) {
            return null;
        }

        return $this->render('@carbon_footprint/event_carbon_footprints/step/attendees.html.twig', [
            'event' => $event,
            'eventId' => $eventId,
            'eventCarbonFootprint' => $eventCarbonFootprint,
            'attendeeSurvey' => $stats->attendeeSurvey,
            'responsesCount' => $stats->responsesCount,
            'estimatedAttendees' => $stats->estimatedAttendees,
            'surveyedPercentage' => $stats->surveyedPercentage,
            'venueLocated' => $stats->venueLocated,
            'legsByDistanceSource' => $stats->legsByDistanceSource,
            'multimodalResponsesCount' => $stats->multimodalResponsesCount,
            'contributions' => $stats->contributions,
            'readOnly' => true,
        ]);
    }
}
