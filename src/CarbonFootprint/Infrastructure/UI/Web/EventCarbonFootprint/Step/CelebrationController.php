<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step;

use App\CarbonFootprint\Application\EventCarbonFootprint\AnswerCelebration\AnswerCelebrationRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\AnswerCelebration\AnswerCelebrationService;
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

#[Route('/events/{eventId}/carbon-footprint/step/celebration', name: 'events_carbon_footprint_celebration')]
class CelebrationController extends AbstractController
{
    use GetEventCarbonFootprintFromEventIdTrait;

    public function __invoke(
        string $eventId,
        EventRepository $eventRepository,
        EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        AnswerCelebrationService $answerCelebration,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $event = $eventRepository->find(new EventId($eventId))
            ?? throw $this->createNotFoundException('Evento no encontrado');

        $eventCarbonFootprint = $this->getEventCarbonFootprintFromEventId($eventId, $eventCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $eventCarbonFootprint);

        if (!$eventCarbonFootprint->status()->isAssemblyAnswered()) {
            return $this->redirectToRoute('events_carbon_footprint_assembly', ['eventId' => $eventId]);
        }

        if ($request->isMethod('POST')) {
            try {
                $answerCelebration(
                    new AnswerCelebrationRequest($eventCarbonFootprint->id()),
                    $loggedUser(),
                );
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            }

            return $this->redirectToRoute('events_carbon_footprint_breakdown', ['eventId' => $eventId]);
        }

        return $this->render('@carbon_footprint/event_carbon_footprints/step/celebration.html.twig', [
            'event' => $event,
            'eventCarbonFootprint' => $eventCarbonFootprint,
            'eventId' => $eventId,
        ]);
    }
}
