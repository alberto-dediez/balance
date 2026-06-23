<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\UI\Web\User\Notification;

use App\Notifications\Application\Notification\Remove\RemoveNotificationRequest;
use App\Notifications\Application\Notification\Remove\RemoveNotificationService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/notifications/{id}/remove', name: 'notification_remove', requirements: ['id' => '[0-9a-fA-F-]{36}'])]
#[IsGranted('ROLE_USER')]
class RemoveController extends AbstractController
{
    public function __construct(private bool $internalNotificationsEnabled = true) {}

    public function __invoke(
        string $id,
        RemoveNotificationService $removeNotification,
        LoggedUser $loggedUser,
    ): Response {
        if (!$this->internalNotificationsEnabled) {
            throw $this->createNotFoundException();
        }

        try {
            $removeNotification(new RemoveNotificationRequest($id), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $this->addFlash('success', new TranslatableMessage('La notificación ha sido eliminada'));

        return $this->redirectToRoute('notifications');
    }
}
