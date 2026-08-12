<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventAttendeeSurvey;

use App\CarbonFootprint\Application\AttendeeSurvey\GetStats\GetAttendeeSurveyStatsRequest;
use App\CarbonFootprint\Application\AttendeeSurvey\GetStats\GetAttendeeSurveyStatsResponse;
use App\CarbonFootprint\Application\AttendeeSurvey\GetStats\GetAttendeeSurveyStatsService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/events/{eventId}/attendee-survey/responses', name: 'events_attendee_survey_responses', methods: ['GET'])]
class ResponsesController extends AbstractController
{
    public function __invoke(
        string $eventId,
        EventRepository $eventRepository,
        EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        GetAttendeeSurveyStatsService $getAttendeeSurveyStats,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
    ): Response {
        $account = $loggedAccount();

        if (null === $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        $event = $eventRepository->find(new EventId($eventId));

        if (null === $event) {
            throw $this->createNotFoundException('Evento no encontrado');
        }

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $event);

        return $this->render('@carbon_footprint/event_attendee_survey/responses.html.twig', [
            'event' => $event,
            'stats' => $this->stats($event->id(), $loggedUser(), $eventCarbonFootprintRepository, $getAttendeeSurveyStats),
        ]);
    }

    /** The event may not have started its carbon footprint yet: the QR still works, there are just no figures to show. */
    private function stats(
        EventId $eventId,
        User $user,
        EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        GetAttendeeSurveyStatsService $getAttendeeSurveyStats,
    ): ?GetAttendeeSurveyStatsResponse {
        $eventCarbonFootprint = $eventCarbonFootprintRepository->findByEventId($eventId);

        if (null === $eventCarbonFootprint) {
            return null;
        }

        try {
            return $getAttendeeSurveyStats(new GetAttendeeSurveyStatsRequest($eventCarbonFootprint->id()), $user);
        } catch (UnauthorizedActionException|\InvalidArgumentException) {
            return null;
        }
    }
}
