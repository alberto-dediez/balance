<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\UI\Web\User\Notification;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications/widget', name: 'notifications_widget', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class WidgetController extends AbstractController
{
    public function __construct(private bool $internalNotificationsEnabled = true) {}

    public function __invoke(): Response
    {
        if (!$this->internalNotificationsEnabled) {
            throw $this->createNotFoundException();
        }

        $response = $this->render('@notifications/user/widget.html.twig');
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
