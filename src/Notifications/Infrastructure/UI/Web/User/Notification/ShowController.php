<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\UI\Web\User\Notification;

use App\Notifications\Application\Notification\NotificationAuthorizer;
use App\Notifications\Application\Notification\Read\MarkNotificationAsReadRequest;
use App\Notifications\Application\Notification\Read\MarkNotificationAsReadService;
use App\Notifications\Domain\Model\Notification\Notification;
use App\Notifications\Domain\Model\Notification\NotificationId;
use App\Notifications\Domain\Model\Notification\NotificationNotFoundException;
use App\Notifications\Domain\Model\Notification\NotificationRepository;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications/{id}', name: 'notification_show', requirements: ['id' => '[0-9a-fA-F-]{36}'])]
#[IsGranted('ROLE_USER')]
class ShowController extends AbstractController
{
    public function __construct(private bool $internalNotificationsEnabled = true) {}

    public function __invoke(
        string $id,
        MarkNotificationAsReadService $markAsRead,
        NotificationRepository $notificationRepository,
        NotificationAuthorizer $authorizer,
        LoggedUser $loggedUser,
    ): Response {
        if (!$this->internalNotificationsEnabled) {
            throw $this->createNotFoundException();
        }

        $user = $loggedUser();

        $notification = $notificationRepository->find(NotificationId::fromString($id));

        if (!$notification instanceof Notification) {
            throw $this->createNotFoundException();
        }

        try {
            $authorizer->denyUnlessCanDoThat($notification, Action::View, $user);

            if ($authorizer->canDoThat($notification, Action::Edit, $user)) {
                $markAsRead(new MarkNotificationAsReadRequest($id), $user);
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (NotificationNotFoundException) {
            throw $this->createNotFoundException();
        }

        $notification = $notificationRepository->find(NotificationId::fromString($id));

        if (!$notification instanceof Notification) {
            throw $this->createNotFoundException();
        }

        return $this->render('@notifications/user/show.html.twig', [
            'notification' => $notification,
        ]);
    }
}
