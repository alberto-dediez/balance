<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\UI\Web\User\Assistant;

use App\Assistant\Application\AssistantSettings;
use App\Assistant\Application\Conversation\SendMessage\SendMessageRequest;
use App\Assistant\Application\Conversation\View\ViewConversationRequest;
use App\Assistant\Application\Conversation\View\ViewConversationResponse;
use App\Assistant\Application\Conversation\View\ViewConversationService;
use App\Assistant\Application\Engine\DailyBudget;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Assistant\Infrastructure\Symfony\Form\SendAssistantMessageType;
use App\Assistant\Infrastructure\UI\Web\AssistantAccess;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/assistant/panel', name: 'assistant_panel', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
class PanelController extends AbstractController
{
    public function __invoke(
        Request $request,
        AssistantAccess $access,
        ViewConversationService $viewConversation,
        DailyBudget $budget,
        AssistantSettings $settings,
        LoggedUser $loggedUser,
    ): Response {
        if (!$access->isGranted()) {
            throw $this->createAccessDeniedException();
        }

        $user = $loggedUser();
        $view = $this->view($request->query->getString('conversationId'), $viewConversation, $user);

        $form = $this->createForm(SendAssistantMessageType::class, new SendMessageRequest($view?->conversation->id()), [
            'action' => $this->generateUrl('assistant_message_send'),
            'document_max_bytes' => $settings->documentMaxBytes,
        ]);

        $response = $this->render('@assistant/panel.html.twig', [
            'form' => $form,
            'view' => $view,
            'spentToday' => $view->spentToday ?? $budget->spentToday($user),
            'dailyLimit' => $view->dailyLimit ?? ($settings->hasDailyLimit() ? $budget->limit() : '0'),
        ]);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }

    private function view(string $conversationId, ViewConversationService $viewConversation, User $user): ?ViewConversationResponse
    {
        if ('' === $conversationId) {
            return null;
        }

        try {
            return $viewConversation(new ViewConversationRequest(new ConversationId($conversationId)), $user);
        } catch (\InvalidArgumentException|UnauthorizedActionException) {
            return null;
        }
    }
}
