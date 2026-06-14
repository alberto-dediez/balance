<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\UI\Web\Admin\Notification;

use App\Notifications\Application\Notification\List\ListNotificationsRequest;
use App\Notifications\Application\Notification\List\ListNotificationsService;
use App\Notifications\Infrastructure\Symfony\Form\ListNotificationsType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/notifications', name: 'admin_notifications')]
#[IsGranted('ROLE_ADMIN')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(ListNotificationsService $listNotifications, LoggedUser $loggedUser, Request $request): Response
    {
        try {
            [$filter, $form] = $this->filter($request, fn(): ListNotificationsRequest => new ListNotificationsRequest(), ListNotificationsType::class);

            $response = $listNotifications($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@notifications/admin/list.html.twig', [
            'notifications' => $response->notifications,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
