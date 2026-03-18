<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\RecordedEvent;

use App\Security\Application\RecordedEvent\List\ListRecordedEventsRequest;
use App\Security\Application\RecordedEvent\List\ListRecordedEventsService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\RecordedEvent\RecordedEvent;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\User\UserRemoved;
use App\Security\Infrastructure\Symfony\Form\ListRecordedEventsType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/events', name: 'admin_events')]
#[IsGranted('ROLE_ADMIN')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(ListRecordedEventsService $listEvents, LoggedUser $loggedUser, Request $request): Response
    {
        new RecordedEvent(
            UserId::generate(),
            new UserRemoved(UserId::generate()),
        );

        [$filter, $form] = $this->filter($request, fn(): ListRecordedEventsRequest => new ListRecordedEventsRequest(), ListRecordedEventsType::class);

        try {
            $response = $listEvents($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@security/admin/events/list.html.twig', [
            'events' => $response->events,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
