<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\UI\Web\User\Assistant;

use App\Assistant\Application\Conversation\List\ListConversationsRequest;
use App\Assistant\Application\Conversation\List\ListConversationsService;
use App\Assistant\Infrastructure\UI\Web\AssistantAccess;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/assistant/conversations', name: 'assistant_conversations', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
class ConversationsController extends AbstractController
{
    private const int PAGE_SIZE = 20;

    public function __invoke(Request $request, AssistantAccess $access, ListConversationsService $listConversations, LoggedUser $loggedUser): Response
    {
        if (!$access->isGranted()) {
            throw $this->createAccessDeniedException();
        }

        $page = max(0, $request->query->getInt('page'));
        $list = new ListConversationsRequest(limit: self::PAGE_SIZE);
        $list->page = $page;

        $result = $listConversations($list, $loggedUser());

        $response = $this->render('@assistant/_conversations.html.twig', [
            'conversations' => $result->conversations,
            'page' => $page,
            'hasMore' => ($page + 1) * self::PAGE_SIZE < $result->totalResults,
            'current' => $request->query->getString('current'),
        ]);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
