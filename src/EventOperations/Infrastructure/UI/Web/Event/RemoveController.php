<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\UI\Web\Event;

use App\EventOperations\Application\Event\Remove\RemoveEventRequest;
use App\EventOperations\Application\Event\Remove\RemoveEventService;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/events/remove/{eventId}', name: 'events_remove')]
class RemoveController extends AbstractController
{
    use GetEventFromIdTrait;

    public function __invoke(
        string $eventId,
        EventRepository $eventRepository,
        RemoveEventService $removeEvent,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
    ): Response {
        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        $event = $this->getEventFromId($eventId, $eventRepository);

        try {
            $removeEvent(new RemoveEventRequest($event->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el evento {event}', ['{event}' => $event->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el evento {event}', ['{event}' => $event->name()]));
        }

        return $this->redirectToRoute('events');
    }
}
