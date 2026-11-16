<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\UI\Web\User\Assistant;

use App\Assistant\Application\Conversation\Rename\RenameConversationRequest;
use App\Assistant\Application\Conversation\Rename\RenameConversationService;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Assistant\Domain\Model\Conversation\InvalidConversationTitleException;
use App\Assistant\Infrastructure\UI\Web\AssistantAccess;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/assistant/conversations/{conversationId}/rename', name: 'assistant_conversation_rename', methods: ['POST'])]
#[CsrfProtected('rename-conversation')]
#[IsGranted('ROLE_ADMIN')]
class RenameConversationController extends AbstractController
{
    public function __invoke(string $conversationId, Request $request, AssistantAccess $access, RenameConversationService $renameConversation, LoggedUser $loggedUser, TranslatorInterface $translator): Response
    {
        if (!$access->isGranted()) {
            throw $this->createAccessDeniedException();
        }

        try {
            $renameConversation(new RenameConversationRequest(new ConversationId($conversationId), $request->request->getString('title')), $loggedUser());
        } catch (InvalidConversationTitleException $exception) {
            return new Response($translator->trans($exception->getMessage()), Response::HTTP_UNPROCESSABLE_ENTITY, ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'no-store']);
        } catch (\InvalidArgumentException|UnauthorizedActionException $exception) {
            throw $this->createNotFoundException($exception->getMessage());
        }

        return new Response(null, Response::HTTP_NO_CONTENT, ['Cache-Control' => 'no-store']);
    }
}
