<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step;

use App\CarbonFootprint\Application\EventCarbonFootprint\AnswerBreakdown\AnswerBreakdownRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\AnswerBreakdown\AnswerBreakdownService;
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

#[Route('/events/{eventId}/carbon-footprint/step/breakdown', name: 'events_carbon_footprint_breakdown')]
class BreakdownController extends AbstractController
{
    use GetEventCarbonFootprintFromEventIdTrait;

    public function __invoke(
        string $eventId,
        EventRepository $eventRepository,
        EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        AnswerBreakdownService $answerBreakdown,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $event = $eventRepository->find(new EventId($eventId))
            ?? throw $this->createNotFoundException('Evento no encontrado');

        $eventCarbonFootprint = $this->getEventCarbonFootprintFromEventId($eventId, $eventCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $eventCarbonFootprint);

        if (!$eventCarbonFootprint->status()->isCelebrationAnswered()) {
            return $this->redirectToRoute('events_carbon_footprint_celebration', ['eventId' => $eventId]);
        }

        if ($request->isMethod('POST')) {
            try {
                $answerBreakdown(
                    new AnswerBreakdownRequest($eventCarbonFootprint->id()),
                    $loggedUser(),
                );
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            }

            return $this->redirectToRoute('events_carbon_footprint_results', ['eventId' => $eventId]);
        }

        return $this->render('@carbon_footprint/event_carbon_footprints/step/breakdown.html.twig', [
            'event' => $event,
            'eventCarbonFootprint' => $eventCarbonFootprint,
            'eventId' => $eventId,
        ]);
    }
}
