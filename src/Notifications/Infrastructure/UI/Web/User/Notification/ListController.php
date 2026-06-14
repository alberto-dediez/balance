<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\UI\Web\User\Notification;

use App\Notifications\Application\Notification\List\ListNotificationsRequest;
use App\Notifications\Application\Notification\List\ListNotificationsService;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Notifications\Infrastructure\Symfony\Form\ListUserNotificationsType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications', name: 'notifications')]
#[IsGranted('ROLE_USER')]
class ListController extends AbstractController
{
    use FilterTrait;

    private const LIMIT = 15;

    public function __invoke(ListNotificationsService $listNotifications, LoggedUser $loggedUser, Request $request): Response
    {
        $user = $loggedUser();

        try {
            [$filter, $form] = $this->filter(
                $request,
                fn(): ListNotificationsRequest => new ListNotificationsRequest(
                    recipientId: $user->id(),
                    channel: NotificationChannel::Internal,
                    limit: self::LIMIT,
                ),
                ListUserNotificationsType::class,
            );

            // The recipient and channel are not exposed in the form: always force them
            // so a stale session or tampered request cannot list other users' notifications.
            $filter->recipientId = $user->id();
            $filter->channel = NotificationChannel::Internal;

            $response = $listNotifications($filter, $user);
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@notifications/user/list.html.twig', [
            'notifications' => $response->notifications,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
