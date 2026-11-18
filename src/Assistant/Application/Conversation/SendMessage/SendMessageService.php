<?php

declare(strict_types=1);

namespace App\Assistant\Application\Conversation\SendMessage;

use App\Assistant\Application\ActionPlan\Discard\DiscardActionPlanRequest;
use App\Assistant\Application\ActionPlan\Discard\DiscardActionPlanService;
use App\Assistant\Application\AssistantSettings;
use App\Assistant\Application\Conversation\Answer\AnswerMessageRequest;
use App\Assistant\Application\Conversation\ConversationAuthorizer;
use App\Assistant\Application\Conversation\Start\StartConversationRequest;
use App\Assistant\Application\Conversation\Start\StartConversationService;
use App\Assistant\Application\Engine\DailyBudget;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanFilter;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanRepository;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanStatus;
use App\Assistant\Domain\Model\ActionPlan\DiscardReason;
use App\Assistant\Domain\Model\Conversation\Conversation;
use App\Assistant\Domain\Model\Conversation\ConversationAttachmentStorage;
use App\Assistant\Domain\Model\Conversation\ConversationBusyException;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Assistant\Domain\Model\Conversation\ConversationRepository;
use App\Assistant\Domain\Model\Conversation\InvalidMessageException;
use App\Assistant\Domain\Model\Conversation\Message;
use App\Assistant\Domain\Model\Conversation\MessageAttachmentDraft;
use App\Assistant\Domain\Model\Conversation\MessageAttachmentId;
use App\Assistant\Domain\Model\Conversation\MessageId;
use App\Assistant\Domain\Model\Conversation\MessageKind;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Assistant\ToolDocument;
use App\Shared\Application\Clock\Clock;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Application\CommandBus\Priority;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class SendMessageService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ConversationRepository $conversations,
        private ActionPlanRepository $plans,
        private ConversationAuthorizer $authorizer,
        private StartConversationService $startConversation,
        private DiscardActionPlanService $discardPlan,
        private DailyBudget $budget,
        private AssistantSettings $settings,
        private ConversationAttachmentStorage $attachmentStorage,
        private Clock $clock,
    ) {}

    public function __invoke(SendMessageRequest $request, User $user): SendMessageResponse
    {
        $conversation = null;

        if (null === $request->conversationId) {
            $this->authorizer->denyUnlessCanDoThat(new Conversation(ConversationId::generate()), Action::Register, $user);
        } else {
            $conversation = $this->conversations->find($request->conversationId);

            if (null === $conversation) {
                throw new \InvalidArgumentException('Conversation not found');
            }

            $this->authorizer->denyUnlessCanDoThat($conversation, Action::Edit, $user);
        }

        if (!$this->settings->enabled) {
            throw new UnauthorizedActionException($conversation ?? new Conversation(ConversationId::generate()), Action::Register);
        }

        $this->assertValidAttachments($request->attachments);
        $this->budget->assertAvailable($user);

        if (null === $conversation) {
            $conversation = $this->conversations->find(($this->startConversation)(new StartConversationRequest(), $user)->conversationId);

            if (null === $conversation) {
                throw new \InvalidArgumentException('Conversation not found');
            }
        }

        $active = $this->plans->totalResultsByFilter(new ActionPlanFilter(conversationId: $conversation->id(), statuses: ActionPlanStatus::active(), limit: 0));

        if ($conversation->isBusy() || $active > 0) {
            throw new ConversationBusyException();
        }

        $now = $this->clock->now();
        $assistantMessageId = MessageId::generate();

        $drafts = [];

        try {
            foreach ($request->attachments as $upload) {
                $drafts[] = $this->store($conversation->id(), $upload);
            }

            $conversation->addUserMessage(MessageId::generate(), $request->text, $request->screenPath, $request->screenTitle, $request->timezone, $now, $drafts);
            $conversation->addPendingAssistantMessage($assistantMessageId, MessageKind::Text, $now);

            foreach ($this->plans->findByFilter(new ActionPlanFilter(conversationId: $conversation->id(), statuses: [ActionPlanStatus::Proposed], limit: 0)) as $plan) {
                ($this->discardPlan)(new DiscardActionPlanRequest($plan->id(), DiscardReason::Superseded), $user);
            }

            $this->conversations->add($conversation);
        } catch (\Throwable $exception) {
            foreach ($drafts as $draft) {
                $this->attachmentStorage->remove($draft->path);
            }

            throw $exception;
        }

        CommandBusDispatcher::instance()->dispatch(
            new DeferredCommand(new AnswerMessageRequest($conversation->id(), $assistantMessageId), Priority::HIGH),
            $user,
        );

        return new SendMessageResponse($conversation->id(), $assistantMessageId);
    }

    /** @param list<MessageAttachmentUpload> $uploads */
    private function assertValidAttachments(array $uploads): void
    {
        if (count($uploads) > Message::ATTACHMENTS_MAX_COUNT) {
            throw InvalidMessageException::tooManyAttachments();
        }

        $total = 0;

        foreach ($uploads as $upload) {
            if (!ToolDocument::isReadable($upload->mimeType)) {
                throw InvalidMessageException::unreadableAttachment($upload->fileName);
            }

            if ($upload->size < 1) {
                throw InvalidMessageException::emptyAttachment($upload->fileName);
            }

            if ($upload->size > $this->settings->documentMaxBytes) {
                throw InvalidMessageException::attachmentTooLarge($upload->fileName);
            }

            if (ToolDocument::isImage($upload->mimeType) && $upload->size > ToolDocument::IMAGE_MAX_BYTES) {
                throw InvalidMessageException::imageTooLarge($upload->fileName, ToolDocument::IMAGE_MAX_BYTES);
            }

            if (ToolDocument::isText($upload->mimeType) && !mb_check_encoding((string) @file_get_contents($upload->localPath), 'UTF-8')) {
                throw InvalidMessageException::notUtf8Attachment($upload->fileName);
            }

            $total += $upload->size;
        }

        if ($total > $this->settings->transcriptAttachmentsMaxBytes) {
            throw InvalidMessageException::attachmentsTooLarge();
        }
    }

    private function store(ConversationId $conversationId, MessageAttachmentUpload $upload): MessageAttachmentDraft
    {
        $id = MessageAttachmentId::generate();
        $path = $this->attachmentStorage->store($conversationId, $id, $upload->extension, $upload->localPath);

        return new MessageAttachmentDraft($id, $upload->fileName, $upload->mimeType, $upload->size, $path);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof SendMessageRequest;
    }
}
