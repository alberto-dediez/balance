<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\UI\Web\Event;

use App\EventOperations\Application\Event\List\ListEventsRequest;
use App\EventOperations\Application\Event\List\ListEventsService;
use App\EventOperations\Infrastructure\Symfony\Form\ListEventsType;
use App\EventOperations\Infrastructure\UI\Web\Event\Bulk\RemoveAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/events', name: 'events')]
class ListController extends AbstractController
{
    use FilterTrait;
    use BulkTrait;

    public function __invoke(
        ListEventsService $listEvents,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        try {
            [$filter, $form] = $this->filter($request, function () use ($account): ListEventsRequest {
                $request = new ListEventsRequest();
                $request->accountId = $account->id();

                return $request;
            }, ListEventsType::class);

            $response = $listEvents($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->events,
            'actions' => [
                new RemoveAction(),
            ],
        ]);

        $needsRedirect = $this->bulkActions($request, $bulkForm, $loggedUser());

        if ($needsRedirect) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@event_operations/events/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'events' => $response->events,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
