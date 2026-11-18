<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\DownloadAttachment;

use App\Assistant\Application\Conversation\ConversationAuthorizer;
use App\Assistant\Domain\Model\Conversation\ConversationAttachmentStorage;
use App\Assistant\Domain\Model\Conversation\ConversationRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

class DownloadConversationAttachmentService
{
    public function __construct(
        private readonly ConversationRepository $conversations,
        private readonly ConversationAttachmentStorage $storage,
        private readonly ConversationAuthorizer $authorizer,
    ) {}

    public function __invoke(DownloadConversationAttachmentRequest $request, User $user): DownloadConversationAttachmentResponse
    {
        $conversation = $this->conversations->find($request->conversationId);

        if (null === $conversation) {
            throw new \InvalidArgumentException('Conversation not found');
        }

        $this->authorizer->denyUnlessCanDoThat($conversation, Action::View, $user);

        $attachment = $conversation->attachmentOf($request->attachmentId);

        if (null === $attachment || !$this->storage->exists($attachment->path())) {
            throw new \InvalidArgumentException('Attachment not found');
        }

        return new DownloadConversationAttachmentResponse(
            $this->storage->read($attachment->path()),
            $attachment->fileName(),
            $attachment->mimeType(),
            $attachment->size(),
        );
    }
}
