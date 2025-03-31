<?php

namespace App\Security\Infrastructure\UI\Web\Admin\RecordedEvent;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\UserId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Security\Domain\Model\User\UserRemoved;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Domain\Model\RecordedEvent\RecordedEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Security\Infrastructure\Symfony\Form\ListRecordedEventsType;
use App\Security\Application\RecordedEvent\List\ListRecordedEventsRequest;
use App\Security\Application\RecordedEvent\List\ListRecordedEventsService;

#[Route('/admin/events', name: 'admin_events')]
#[IsGranted("ROLE_ADMIN")]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(ListRecordedEventsService $listEvents, Request $request): Response
    {   
        $event = new RecordedEvent(
            UserId::generate(), 
            new UserRemoved(UserId::generate())
        );

        $this->denyAccessUnlessGranted(Action::List->value, $event);

        list($filter, $form) = $this->filter($request, function () {
            return  new ListRecordedEventsRequest();
        }, ListRecordedEventsType::class);

        $response = $listEvents($filter);

        return $this->render('@security/admin/events/list.html.twig', [
            'events' => $response->events,
            'totalResults' => $response->totalResults,
            'form' => $form->createView()
        ]);
    }
}
