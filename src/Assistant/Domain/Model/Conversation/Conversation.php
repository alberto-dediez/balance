<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\Conversation;

use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Assistant\Domain\Model\AssistantUsage;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Conversation
{
    use AggregateRoot;

    public const int TITLE_MAX_LENGTH = 120;
    public const int MESSAGE_MAX_LENGTH = 4000;
    public const string DEFAULT_TITLE = 'Nueva conversación';

    /**
     * @param Collection<int, Message> $messages
     * @param DomainEvent[]            $events
     */
    public function __construct(
        private ConversationId $id,
        private ?UserId $userId = null,
        private string $title = self::DEFAULT_TITLE,
        private \DateTimeImmutable $startedAt = new \DateTimeImmutable('NOW'),
        private \DateTimeImmutable $lastActivityAt = new \DateTimeImmutable('NOW'),
        private Collection $messages = new ArrayCollection(),
        array $events = [],
    ) {
        $this->events = $events;
    }

    public function id(): ConversationId
    {
        return $this->id;
    }

    public function userId(): ?UserId
    {
        return $this->userId;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function startedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function lastActivityAt(): \DateTimeImmutable
    {
        return $this->lastActivityAt;
    }

    /** @return list<Message> */
    public function messages(): array
    {
        $messages = array_values($this->messages->toArray());

        usort($messages, static fn(Message $a, Message $b): int => $a->position() <=> $b->position());

        return $messages;
    }

    public function messageOf(MessageId $id): ?Message
    {
        foreach ($this->messages as $message) {
            if ($message->id()->equals($id)) {
                return $message;
            }
        }

        return null;
    }

    public function pendingMessage(): ?Message
    {
        foreach ($this->messages() as $message) {
            if ($message->isPending()) {
                return $message;
            }
        }

        return null;
    }

    public function isBusy(): bool
    {
        return null !== $this->pendingMessage();
    }

    public function lastUserMessage(): ?Message
    {
        $last = null;

        foreach ($this->messages() as $message) {
            if ($message->isUser()) {
                $last = $message;
            }
        }

        return $last;
    }

    public function isOf(UserId $userId): bool
    {
        return null !== $this->userId && $this->userId->equals($userId);
    }

    public function isEmpty(): bool
    {
        return 0 === $this->messages->count();
    }

    public function start(UserId $userId, \DateTimeImmutable $now): void
    {
        $this->recordApplyAndPublish(new ConversationStarted(
            conversationId: $this->id,
            userId: $userId,
            occurredOn: $now,
        ));
    }

    /** @param list<MessageAttachmentDraft> $attachments */
    public function addUserMessage(MessageId $id, string $text, string $screenPath, string $screenTitle, string $timezone, \DateTimeImmutable $now, array $attachments = []): void
    {
        $text = trim($text);

        if ('' === $text) {
            throw InvalidMessageException::empty();
        }

        if (mb_strlen($text) > self::MESSAGE_MAX_LENGTH) {
            throw InvalidMessageException::tooLong(self::MESSAGE_MAX_LENGTH);
        }

        if (count($attachments) > Message::ATTACHMENTS_MAX_COUNT) {
            throw InvalidMessageException::tooManyAttachments();
        }

        if ($this->isBusy()) {
            throw new ConversationBusyException();
        }

        if (!$this->hasUserMessages()) {
            $this->title = mb_substr(trim((string) preg_replace('/\s+/u', ' ', $text)), 0, self::TITLE_MAX_LENGTH);
        }

        $message = new Message(
            id: $id,
            conversation: $this,
            position: $this->nextPosition(),
            role: MessageRole::User,
            kind: MessageKind::Text,
            status: MessageStatus::Completed,
            text: $text,
            createdAt: $now,
            screenPath: $this->limited($screenPath, Message::SCREEN_PATH_MAX_LENGTH),
            screenTitle: $this->limited($screenTitle, Message::SCREEN_TITLE_MAX_LENGTH),
            timezone: $this->limited($timezone, Message::TIMEZONE_MAX_LENGTH),
            completedAt: $now,
        );

        foreach ($attachments as $draft) {
            $message->attach($draft);
        }

        $this->messages->add($message);

        $this->recordApplyAndPublish(new ConversationMessageAdded(
            conversationId: $this->id,
            messageId: $id,
            role: MessageRole::User,
            kind: MessageKind::Text,
            attachmentsCount: count($attachments),
            occurredOn: $now,
        ));
    }

    public function addPendingAssistantMessage(MessageId $id, MessageKind $kind, \DateTimeImmutable $now, ?ActionPlanId $planId = null): void
    {
        if ($this->isBusy()) {
            throw new ConversationBusyException();
        }

        $this->messages->add(new Message(
            id: $id,
            conversation: $this,
            position: $this->nextPosition(),
            role: MessageRole::Assistant,
            kind: $kind,
            status: MessageStatus::Pending,
            text: '',
            createdAt: $now,
            planId: $planId,
        ));

        $this->recordApplyAndPublish(new ConversationMessageAdded(
            conversationId: $this->id,
            messageId: $id,
            role: MessageRole::Assistant,
            kind: $kind,
            occurredOn: $now,
        ));
    }

    /** @param array<string, list<string>> $arguments */
    public function recordToolCall(MessageId $messageId, ToolCallId $id, string $tool, array $arguments, string $summary, bool $failed, int $durationMs, ?string $result = null): void
    {
        $this->pendingMessageOf($messageId)->recordToolCall($id, $tool, $arguments, $summary, $failed, $durationMs, $result);
    }

    public function attachmentOf(MessageAttachmentId $id): ?MessageAttachment
    {
        foreach ($this->messages as $message) {
            $attachment = $message->attachmentOf($id);

            if (null !== $attachment) {
                return $attachment;
            }
        }

        return null;
    }

    /** @return list<string> */
    public function attachmentPaths(): array
    {
        $paths = [];

        foreach ($this->messages() as $message) {
            foreach ($message->attachments() as $attachment) {
                $paths[] = $attachment->path();
            }
        }

        return $paths;
    }

    public function rename(string $title, \DateTimeImmutable $now): void
    {
        $title = mb_substr(trim((string) preg_replace('/\s+/u', ' ', $title)), 0, self::TITLE_MAX_LENGTH + 1);

        if ('' === $title) {
            throw InvalidConversationTitleException::empty();
        }

        if (mb_strlen($title) > self::TITLE_MAX_LENGTH) {
            throw InvalidConversationTitleException::tooLong(self::TITLE_MAX_LENGTH);
        }

        $this->title = $title;

        $this->recordApplyAndPublish(new ConversationRenamed(
            conversationId: $this->id,
            userId: $this->userId ?? throw new \LogicException('Conversation without user'),
            occurredOn: $now,
        ));
    }

    public function remove(\DateTimeImmutable $now): void
    {
        if ($this->isBusy()) {
            throw new ConversationBusyException();
        }

        $this->recordApplyAndPublish(new ConversationRemoved(
            conversationId: $this->id,
            userId: $this->userId ?? throw new \LogicException('Conversation without user'),
            occurredOn: $now,
        ));
    }

    public function completeMessage(MessageId $id, string $text, MessageKind $kind, ?ActionPlanId $planId, AssistantUsage $usage, \DateTimeImmutable $now): void
    {
        $message = $this->pendingMessageOf($id);
        $message->complete(trim($text), $kind, $planId, $usage, $now);

        $this->recordApplyAndPublish(new ConversationMessageCompleted(
            conversationId: $this->id,
            messageId: $id,
            kind: $kind,
            planId: $message->planId(),
            toolCalls: count($message->toolCalls()),
            costUsd: $usage->costUsd(),
            occurredOn: $now,
        ));
    }

    public function failMessage(MessageId $id, string $error, AssistantUsage $usage, \DateTimeImmutable $now): void
    {
        $this->pendingMessageOf($id)->fail($error, $usage, $now);

        $this->recordApplyAndPublish(new ConversationMessageFailed(
            conversationId: $this->id,
            messageId: $id,
            occurredOn: $now,
        ));
    }

    public function applyConversationStarted(ConversationStarted $event): void
    {
        $this->userId = $event->userId();
        $this->title = self::DEFAULT_TITLE;
        $this->startedAt = $event->occurredOn();
        $this->lastActivityAt = $event->occurredOn();
    }

    public function applyConversationRenamed(ConversationRenamed $event): void {}

    public function applyConversationRemoved(ConversationRemoved $event): void {}

    public function applyConversationMessageAdded(ConversationMessageAdded $event): void
    {
        $this->lastActivityAt = $event->occurredOn();
    }

    public function applyConversationMessageCompleted(ConversationMessageCompleted $event): void
    {
        $this->lastActivityAt = $event->occurredOn();
    }

    public function applyConversationMessageFailed(ConversationMessageFailed $event): void
    {
        $this->lastActivityAt = $event->occurredOn();
    }

    private function pendingMessageOf(MessageId $id): Message
    {
        $message = $this->messageOf($id);

        if (null === $message) {
            throw InvalidMessageException::notFound($id);
        }

        if (!$message->isPending()) {
            throw InvalidMessageException::notPending($id);
        }

        return $message;
    }

    private function hasUserMessages(): bool
    {
        foreach ($this->messages as $message) {
            if ($message->isUser()) {
                return true;
            }
        }

        return false;
    }

    private function nextPosition(): int
    {
        $max = 0;

        foreach ($this->messages as $message) {
            $max = max($max, $message->position());
        }

        return $max + 1;
    }

    private function limited(string $value, int $maxLength): ?string
    {
        $value = trim($value);

        return '' === $value ? null : mb_substr($value, 0, $maxLength);
    }
}
