<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\UI\Web\User\Assistant;

use App\Assistant\Application\Conversation\DownloadAttachment\DownloadConversationAttachmentRequest;
use App\Assistant\Application\Conversation\DownloadAttachment\DownloadConversationAttachmentService;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Assistant\Domain\Model\Conversation\MessageAttachmentId;
use App\Assistant\Infrastructure\UI\Web\AssistantAccess;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/assistant/conversations/{conversationId}/attachments/{attachmentId}', name: 'assistant_attachment_download', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
class AttachmentController extends AbstractController
{
    public function __invoke(string $conversationId, string $attachmentId, AssistantAccess $access, DownloadConversationAttachmentService $downloadAttachment, LoggedUser $loggedUser): Response
    {
        if (!$access->isGranted()) {
            throw $this->createAccessDeniedException();
        }

        try {
            $file = $downloadAttachment(new DownloadConversationAttachmentRequest(new ConversationId($conversationId), new MessageAttachmentId($attachmentId)), $loggedUser());
        } catch (\InvalidArgumentException|UnauthorizedActionException $exception) {
            throw $this->createNotFoundException($exception->getMessage());
        }

        return new Response($file->contents, Response::HTTP_OK, [
            'Content-Type' => $file->mimeType,
            'Content-Length' => (string) $file->size,
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $file->fileName, 'attachment'),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store',
        ]);
    }
}
