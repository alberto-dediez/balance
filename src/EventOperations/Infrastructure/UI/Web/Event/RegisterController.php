<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\UI\Web\Event;

use App\EventOperations\Application\Event\Register\RegisterEventRequest;
use App\EventOperations\Application\Event\Register\RegisterEventService;
use App\EventOperations\Domain\Model\Event\Event;
use App\EventOperations\Domain\Model\Event\EventId;
use App\EventOperations\Infrastructure\Symfony\Form\RegisterEventType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/events/register', name: 'events_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(
        RegisterEventService $registerEvent,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new Event(
            id: EventId::generate(),
            accountId: $account->id(),
        ));

        $form = $this->createForm(RegisterEventType::class, new RegisterEventRequest());

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerEvent, $account, $loggedUser) {
                $registerRequest = $form->getData();
                $registerRequest->accountId = $account->id();

                $response = $registerEvent($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Evento creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('events');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@event_operations/events/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
