<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\UI\Web\Event;

use App\EventOperations\Application\Event\Update\UpdateEventRequest;
use App\EventOperations\Application\Event\Update\UpdateEventService;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\EventOperations\Infrastructure\Symfony\Form\UpdateEventType;
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

#[Route('/events/edit/{eventId}', name: 'events_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetEventFromIdTrait;

    public function __invoke(
        string $eventId,
        EventRepository $eventRepository,
        UpdateEventService $updateEvent,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $account = $loggedAccount();

        if (null === $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        $event = $this->getEventFromId($eventId, $eventRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $event);

        $eventRequest = new UpdateEventRequest(
            eventId: $event->id(),
            name: $event->name(),
            location: $event->location(),
            size: $event->size(),
            estimatedAttendees: $event->estimatedAttendees(),
            sector: $event->sector(),
            typology: $event->typology(),
            setupStartDate: $event->setup()->startDate(),
            setupEndDate: $event->setup()->endDate(),
            celebrationStartDate: $event->celebration()->startDate(),
            celebrationEndDate: $event->celebration()->endDate(),
            dismantlingStartDate: $event->dismantling()->startDate(),
            dismantlingEndDate: $event->dismantling()->endDate(),
        );

        try {
            $form = $this->createForm(UpdateEventType::class, $eventRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateEvent, $loggedUser): void {
                $updateRequest = $form->getData();

                $updateEvent($updateRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Evento actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('events');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@event_operations/events/edit.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }
}
