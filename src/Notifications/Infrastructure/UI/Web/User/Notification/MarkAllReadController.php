<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\UI\Web\User\Notification;

use App\Notifications\Application\Notification\Read\MarkAllNotificationsAsReadService;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/notifications/read-all', name: 'notifications_mark_all_read', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
#[CsrfProtected('mark_all_read')]
class MarkAllReadController extends AbstractController
{
    public function __construct(private bool $internalNotificationsEnabled = true) {}

    public function __invoke(
        MarkAllNotificationsAsReadService $markAllAsRead,
        LoggedUser $loggedUser,
        Request $request,
        TranslatorInterface $translator,
    ): Response {
        if (!$this->internalNotificationsEnabled) {
            throw $this->createNotFoundException();
        }

        $markAllAsRead($loggedUser());

        $message = new TranslatableMessage('Todas las notificaciones se han marcado como leídas');

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['message' => $message->trans($translator)]);
        }

        $this->addFlash('success', $message);

        return $this->redirectToRoute('notifications');
    }
}
