<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint;

use App\CarbonFootprint\Application\EventCarbonFootprint\Update\UpdateEventCarbonFootprintRequest;
use App\CarbonFootprint\Application\EventCarbonFootprint\Update\UpdateEventCarbonFootprintService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateEventCarbonFootprintType;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/events/{eventId}/carbon-footprint/edit', name: 'events_carbon_footprint_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetEventCarbonFootprintFromEventIdTrait;

    public function __invoke(
        string $eventId,
        EventRepository $eventRepository,
        EventCarbonFootprintRepository $eventCarbonFootprintRepository,
        UpdateEventCarbonFootprintService $updateEventCarbonFootprint,
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

        $eventCarbonFootprint = $this->getEventCarbonFootprintFromEventId($eventId, $eventCarbonFootprintRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $eventCarbonFootprint);

        $eventCarbonFootprintRequest = new UpdateEventCarbonFootprintRequest(
            eventCarbonFootprintId: $eventCarbonFootprint->id(),
            calculationPeriodStartDate: $eventCarbonFootprint->calculationPeriod()->startDate(),
            calculationPeriodEndDate: $eventCarbonFootprint->calculationPeriod()->endDate(),
            totalEmissions: $eventCarbonFootprint->totalEmissions(),
            emissionsPerAttendee: $eventCarbonFootprint->emissionsPerAttendee(),
            status: $eventCarbonFootprint->status(),
        );

        try {
            $form = $this->createForm(UpdateEventCarbonFootprintType::class, $eventCarbonFootprintRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateEventCarbonFootprint, $loggedUser): void {
                $updateRequest = $form->getData();

                $updateEventCarbonFootprint($updateRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Cálculo de Huella de Carbono actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('events');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/event_carbon_footprints/edit.html.twig', [
            'event' => $event,
            'eventCarbonFootprint' => $eventCarbonFootprint,
            'form' => $form->createView(),
        ]);
    }
}
