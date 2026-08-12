<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventAttendeeSurvey;

use App\CarbonFootprint\Domain\Model\PublicLanding\PublicLandingOwnerType;
use App\CarbonFootprint\Infrastructure\UI\Web\PublicLanding\PublicLandingForm;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/events/{eventId}/attendee-survey', name: 'events_attendee_survey', methods: ['GET', 'POST'])]
class IndexController extends AbstractController
{
    public function __invoke(
        string $eventId,
        EventRepository $eventRepository,
        PublicLandingForm $publicLandingForm,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
        Request $request,
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

        try {
            $landingForm = $publicLandingForm->handle(
                PublicLandingOwnerType::Event,
                $eventId,
                $loggedUser(),
                $request,
            );
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        if ($landingForm->isSubmitted() && $landingForm->isValid()) {
            return $this->redirectToRoute('events_attendee_survey', ['eventId' => $eventId]);
        }

        return $this->render('@carbon_footprint/event_attendee_survey/index.html.twig', [
            'event' => $event,
            'landingForm' => $landingForm->createView(),
        ]);
    }
}
