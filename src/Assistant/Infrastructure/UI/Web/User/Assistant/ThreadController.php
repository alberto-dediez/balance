<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\UI\Web\User\Assistant;

use App\Assistant\Application\Conversation\View\ViewConversationRequest;
use App\Assistant\Application\Conversation\View\ViewConversationService;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Assistant\Infrastructure\UI\Web\AssistantAccess;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/assistant/conversations/{conversationId}/thread', name: 'assistant_thread', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
class ThreadController extends AbstractController
{
    public function __invoke(string $conversationId, AssistantAccess $access, ViewConversationService $viewConversation, LoggedUser $loggedUser): Response
    {
        if (!$access->isGranted()) {
            throw $this->createAccessDeniedException();
        }

        try {
            $view = $viewConversation(new ViewConversationRequest(new ConversationId($conversationId)), $loggedUser());
        } catch (\InvalidArgumentException|UnauthorizedActionException $exception) {
            throw $this->createNotFoundException($exception->getMessage());
        }

        $response = $this->render('@assistant/_thread.html.twig', ['view' => $view]);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
