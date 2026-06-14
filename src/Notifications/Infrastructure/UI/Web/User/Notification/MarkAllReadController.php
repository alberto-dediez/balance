<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\UI\Web\User\Notification;

use App\Notifications\Application\Notification\Read\MarkAllNotificationsAsReadService;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/notifications/read-all', name: 'notifications_mark_all_read', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
class MarkAllReadController extends AbstractController
{
    public function __invoke(MarkAllNotificationsAsReadService $markAllAsRead, LoggedUser $loggedUser): Response
    {
        $markAllAsRead($loggedUser());

        $this->addFlash('success', new TranslatableMessage('Todas las notificaciones se han marcado como leídas'));

        return $this->redirectToRoute('notifications');
    }
}
