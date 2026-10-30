<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\UI\Web\User\Notification;

use App\Notifications\Application\Notification\List\ListNotificationsRequest;
use App\Notifications\Application\Notification\List\ListNotificationsService;
use App\Notifications\Application\Notification\Unread\UnreadNotificationsProvider;
use App\Notifications\Domain\Model\Notification\NotificationChannel;
use App\Notifications\Infrastructure\Symfony\Form\ListUserNotificationsType;
use App\Notifications\Infrastructure\UI\Web\User\Notification\Bulk\MarkAsReadAction;
use App\Notifications\Infrastructure\UI\Web\User\Notification\Bulk\RemoveAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\Symfony\Form\BulkType;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use App\Shared\Infrastructure\UI\Web\CRUD\BulkTrait;
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
    use BulkTrait;

    private const LIMIT = 15;

    public function __construct(private bool $internalNotificationsEnabled = true) {}

    public function __invoke(
        ListNotificationsService $listNotifications,
        UnreadNotificationsProvider $unreadNotifications,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        if (!$this->internalNotificationsEnabled) {
            throw $this->createNotFoundException();
        }

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

        $bulkForm = $this->createForm(BulkType::class, new BulkRequest(), [
            'elements' => $response->notifications,
            'actions' => [
                new MarkAsReadAction(),
                new RemoveAction(),
            ],
        ]);

        if ($this->bulkActions($request, $bulkForm, $user)) {
            return $this->redirect($request->getRequestUri());
        }

        return $this->render('@notifications/user/list.html.twig', [
            'bulkForm' => $bulkForm->createView(),
            'notifications' => $response->notifications,
            'totalResults' => $response->totalResults,
            'unreadCount' => $unreadNotifications->count(),
            'form' => $form->createView(),
        ]);
    }
}
