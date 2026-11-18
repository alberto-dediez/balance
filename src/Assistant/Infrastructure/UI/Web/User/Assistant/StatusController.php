<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\UI\Web\User\Assistant;

use App\Assistant\Application\Conversation\ViewStatus\ViewConversationStatusRequest;
use App\Assistant\Application\Conversation\ViewStatus\ViewConversationStatusService;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Assistant\Infrastructure\UI\Web\AssistantAccess;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/assistant/conversations/{conversationId}/status', name: 'assistant_status', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
class StatusController extends AbstractController
{
    public function __invoke(string $conversationId, AssistantAccess $access, ViewConversationStatusService $viewStatus, LoggedUser $loggedUser): JsonResponse
    {
        if (!$access->isGranted()) {
            throw $this->createAccessDeniedException();
        }

        try {
            $status = $viewStatus(new ViewConversationStatusRequest(new ConversationId($conversationId)), $loggedUser());
        } catch (\InvalidArgumentException|UnauthorizedActionException $exception) {
            throw $this->createNotFoundException($exception->getMessage());
        }

        $response = new JsonResponse(['fingerprint' => $status->fingerprint, 'busy' => $status->busy]);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
