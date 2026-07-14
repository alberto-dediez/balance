<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step;

use App\CarbonFootprint\Application\AttendeeSurvey\GetStats\GetAttendeeSurveyStatsRequest;
use App\CarbonFootprint\Application\AttendeeSurvey\GetStats\GetAttendeeSurveyStatsService;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetResults\GetResultsRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\GetResults\GetResultsService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\GetEventCarbonFootprintFromEventIdTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\View\ResultsView;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        GetAttendeeSurveyStatsService $getAttendeeSurveyStatsService,
        EventCarbonFootprintReportRepository $reportRepository,
        ResultsView $resultsView,
        LoggedUser $loggedUser,
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
}
