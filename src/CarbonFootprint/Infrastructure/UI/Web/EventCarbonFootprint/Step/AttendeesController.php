<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step;

use App\CarbonFootprint\Application\AttendeeSurvey\Close\CloseAttendeeSurveyRequest;
use App\CarbonFootprint\Application\AttendeeSurvey\Close\CloseAttendeeSurveyService;
use App\CarbonFootprint\Application\AttendeeSurvey\GetStats\GetAttendeeSurveyStatsRequest;
use App\CarbonFootprint\Application\AttendeeSurvey\GetStats\GetAttendeeSurveyStatsService;
use App\CarbonFootprint\Application\AttendeeSurvey\Register\RegisterAttendeeSurveyRequest;
use App\CarbonFootprint\Application\AttendeeSurvey\Register\RegisterAttendeeSurveyService;
use App\CarbonFootprint\Application\EmployeeParticipation\GetStats\GetEmployeeParticipationStatsRequest;
use App\CarbonFootprint\Application\EmployeeParticipation\GetStats\GetEmployeeParticipationStatsService;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\EmployeeParticipation\EmployeeParticipationRepository;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\GetEventCarbonFootprintFromEventIdTrait;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/events/{eventId}/carbon-footprint/step/attendees', name: 'events_carbon_footprint_attendees')]
class AttendeesController extends AbstractController
{
    use GetEventCarbonFootprintFromEventIdTrait;

    public function __invoke(
        string $eventId,
        EventRepository $eventRepository,
        EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        RegisterAttendeeSurveyService $registerAttendeeSurvey,
        GetAttendeeSurveyStatsService $getAttendeeSurveyStats,
        CloseAttendeeSurveyService $closeAttendeeSurvey,
        GetEmployeeParticipationStatsService $getEmployeeParticipationStats,
        EmployeeParticipationRepository $employeeParticipationRepository,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $event = $eventRepository->find(new EventId($eventId))
            ?? throw $this->createNotFoundException('Evento no encontrado');

        $eventCarbonFootprint = $this->getEventCarbonFootprintFromEventId($eventId, $eventCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $eventCarbonFootprint);

        if ($eventCarbonFootprint->status()->isCompleted()) {
            return $this->redirectToRoute('events_carbon_footprint_results', ['eventId' => $eventId, 'step' => 'attendees']);
        }

        if (!$eventCarbonFootprint->status()->isEmployeesAnswered()) {
            return $this->redirectToRoute('events_carbon_footprint_employees', ['eventId' => $eventId]);
        }

        $registerAttendeeSurvey(new RegisterAttendeeSurveyRequest($event->id()), $loggedUser());

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('attendees_' . $eventCarbonFootprint->id(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }

            $realAttendees = $request->request->getInt('realAttendees');
            $confidenceLevel = ConfidenceLevel::tryFrom($request->request->getInt('confidenceLevel'));

            if ($realAttendees < 1 || null === $confidenceLevel) {
                $this->addFlash('danger', new TranslatableMessage('Introduce un número de asistentes reales válido y un nivel de confianza.'));

                return $this->redirectToRoute('events_carbon_footprint_attendees', ['eventId' => $eventId]);
            }

            try {
                $statsBeforeClose = $getAttendeeSurveyStats(
                    new GetAttendeeSurveyStatsRequest($eventCarbonFootprint->id()),
                    $loggedUser(),
                );

                if (0 === $statsBeforeClose->responsesCount) {
                    $this->addFlash('warning', new TranslatableMessage('La encuesta no tiene respuestas: se han conservado las actividades manuales de asistentes.'));
                }

                if (null !== $employeeParticipationRepository->findByEventCarbonFootprintId($eventCarbonFootprint->id())) {
                    $employeeStats = $getEmployeeParticipationStats(
                        new GetEmployeeParticipationStatsRequest($eventCarbonFootprint->id()),
                        $loggedUser(),
                    );

                    if (0 === $employeeStats->answeredCount) {
                        $this->addFlash('warning', new TranslatableMessage('Los empleados no tienen respuestas: se han conservado las actividades manuales de empleados.'));
                    }
                }

                $closeResponse = $closeAttendeeSurvey(
                    new CloseAttendeeSurveyRequest($eventCarbonFootprint->id(), $realAttendees, $confidenceLevel),
                    $loggedUser(),
                );

                if ($closeResponse->overnightStayCatalogMissing) {
                    $this->addFlash('warning', new TranslatableMessage('No hay ningún alojamiento del catálogo marcado para asistentes: no se han generado pernoctaciones.'));
                }
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('danger', new TranslatableMessage('No se ha podido cerrar la encuesta: {error}', ['{error}' => $e->getMessage()]));

                return $this->redirectToRoute('events_carbon_footprint_attendees', ['eventId' => $eventId]);
            }

            return $this->redirectToRoute('events_carbon_footprint_results', ['eventId' => $eventId]);
        }

        $stats = $getAttendeeSurveyStats(
            new GetAttendeeSurveyStatsRequest($eventCarbonFootprint->id()),
            $loggedUser(),
        );

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
            'confidenceLevels' => ConfidenceLevel::cases(),
        ]);
    }
}
