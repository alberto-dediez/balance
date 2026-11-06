<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\UI\Web\User\Notification;

use App\Notifications\Application\Notification\Unread\MarkNotificationAsUnreadRequest;
use App\Notifications\Application\Notification\Unread\MarkNotificationAsUnreadService;
use App\Notifications\Domain\Model\Notification\NotificationNotFoundException;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/notifications/{id}/unread', name: 'notification_mark_unread', requirements: ['id' => '[0-9a-fA-F-]{36}'])]
#[CsrfProtected('mark_as_unread')]
#[IsGranted('ROLE_USER')]
class MarkAsUnreadController extends AbstractController
{
    public function __construct(private bool $internalNotificationsEnabled = true) {}

    public function __invoke(
        string $id,
        MarkNotificationAsUnreadService $markAsUnread,
        LoggedUser $loggedUser,
    ): Response {
        if (!$this->internalNotificationsEnabled) {
            throw $this->createNotFoundException();
        }

        try {
            $markAsUnread(new MarkNotificationAsUnreadRequest($id), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (NotificationNotFoundException) {
            throw $this->createNotFoundException();
        }

        $this->addFlash('success', new TranslatableMessage('La notificación se ha marcado como no leída'));

        return $this->redirectToRoute('notifications');
    }
}
