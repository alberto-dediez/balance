<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint;

use App\CarbonFootprint\Application\EventCarbonFootprint\Register\RegisterEventCarbonFootprintRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\Register\RegisterEventCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/events/{eventId}/carbon-footprint/register', name: 'events_carbon_footprint_register')]
class RegisterController extends AbstractController
{
    public function __invoke(
        string $eventId,
        EventRepository $eventRepository,
        EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        RegisterEventCarbonFootprintService $registerEventCarbonFootprint,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
    ): Response {
        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        $event = $eventRepository->find(new EventId($eventId));

        if (null === $event) {
            throw $this->createNotFoundException('Evento no encontrado');
        }

        $eventAccountId = $event->accountId();

        if (null === $eventAccountId || !$eventAccountId->equals($account->id())) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        $existing = $eventCarbonFootprintRepository->findByEventId($event->id());

        if (null !== $existing) {
            return $this->redirectToRoute('events_carbon_footprint_edit', ['eventId' => $eventId]);
        }

        try {
            $registerEventCarbonFootprint(new RegisterEventCarbonFootprintRequest(
                eventId: $event->id(),
                accountId: $account->id(),
                calculationPeriodStartDate: $event->setup()->startDate(),
                calculationPeriodEndDate: $event->dismantling()->endDate(),
            ), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Cálculo de Huella de Carbono creado correctamente'));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->redirectToRoute('events_carbon_footprint_edit', ['eventId' => $eventId]);
    }
}
