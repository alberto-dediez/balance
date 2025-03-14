<?php

namespace App\Security\Infrastructure\UI\Web\Admin\RecordedEvent;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Security\Application\RecordedEvent\List\ListRecordedEventsRequest;
use App\Security\Application\RecordedEvent\List\ListRecordedEventsService;
use App\Security\Infrastructure\Symfony\Form\ListRecordedEventsType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin/events', name: 'admin_events')]
#[IsGranted("ROLE_ADMIN")]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(ListRecordedEventsService $listEvents, Request $request): Response
    {   
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
