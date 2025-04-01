<?php

namespace App\Security\Infrastructure\UI\Web\Admin\RecordedEvent;

use App\Security\Domain\Model\User\UserId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Security\Domain\Model\User\UserRemoved;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Domain\Model\RecordedEvent\RecordedEvent;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Security\Infrastructure\Symfony\Form\ListRecordedEventsType;
use App\Security\Application\RecordedEvent\List\ListRecordedEventsRequest;
use App\Security\Application\RecordedEvent\List\ListRecordedEventsService;
use App\Security\Application\UnauthorizedActionException;

#[Route('/admin/events', name: 'admin_events')]
#[IsGranted("ROLE_ADMIN")]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(ListRecordedEventsService $listEvents, LoggedUser $loggedUser, Request $request): Response
    {   
        $event = new RecordedEvent(
            UserId::generate(), 
            new UserRemoved(UserId::generate())
        );

        list($filter, $form) = $this->filter($request, function () {
            return  new ListRecordedEventsRequest();
        }, ListRecordedEventsType::class);

        try {
            $response = $listEvents($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }       

        return $this->render('@security/admin/events/list.html.twig', [
            'events' => $response->events,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
