<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\Conversation;

use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Assistant\Domain\Model\AssistantUsage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Message
{
    public const int ATTACHMENTS_MAX_COUNT = 5;
    public const int SCREEN_PATH_MAX_LENGTH = 512;
    public const int SCREEN_TITLE_MAX_LENGTH = 255;
    public const int TIMEZONE_MAX_LENGTH = 64;

    /**
     * @param Collection<int, ToolCall>          $toolCalls
     * @param Collection<int, MessageAttachment> $attachments
     */
    public function __construct(
        private MessageId $id,
        private Conversation $conversation,
        private int $position,
        private MessageRole $role,
        private MessageKind $kind,
        private MessageStatus $status,
        private string $text,
        private \DateTimeImmutable $createdAt,
        private ?string $screenPath = null,
        private ?string $screenTitle = null,
        private ?string $timezone = null,
        private ?string $error = null,
        private ?ActionPlanId $planId = null,
        private AssistantUsage $usage = new AssistantUsage(),
        private ?\DateTimeImmutable $completedAt = null,
        private Collection $toolCalls = new ArrayCollection(),
        private Collection $attachments = new ArrayCollection(),
    ) {}

    public function id(): MessageId
    {
        return $this->id;
    }

    public function conversation(): Conversation
    {
        return $this->conversation;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function role(): MessageRole
    {
        return $this->role;
    }

    public function kind(): MessageKind
    {
        return $this->kind;
    }

    public function status(): MessageStatus
    {
        return $this->status;
    }

    public function text(): string
    {
        return $this->text;
    }

    public function error(): ?string
    {
        return $this->error;
    }

    public function screenPath(): ?string
    {
        return $this->screenPath;
    }

    public function screenTitle(): ?string
    {
        return $this->screenTitle;
    }

    public function timezone(): ?string
    {
        return $this->timezone;
    }

    public function planId(): ?ActionPlanId
    {
        return $this->planId;
    }

    public function usage(): AssistantUsage
    {
        return $this->usage;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function completedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function isUser(): bool
    {
        return MessageRole::User === $this->role;
    }

    public function isAssistant(): bool
    {
        return MessageRole::Assistant === $this->role;
    }

    public function isPending(): bool
    {
        return MessageStatus::Pending === $this->status;
    }

    public function isCompleted(): bool
    {
        return MessageStatus::Completed === $this->status;
    }

    public function isFailed(): bool
    {
        return MessageStatus::Failed === $this->status;
    }

    /** @return list<ToolCall> */
    public function toolCalls(): array
    {
        $calls = array_values($this->toolCalls->toArray());

        usort($calls, static fn(ToolCall $a, ToolCall $b): int => $a->position() <=> $b->position());

        return $calls;
    }

    /** @param array<string, list<string>> $arguments */
    public function recordToolCall(ToolCallId $id, string $tool, array $arguments, string $summary, bool $failed, int $durationMs, ?string $result = null): void
    {
        $this->toolCalls->add(ToolCall::record($id, $this, $this->toolCalls->count() + 1, $tool, $arguments, $summary, $failed, $durationMs, $result));
    }

    /** @return list<MessageAttachment> */
    public function attachments(): array
    {
        $attachments = array_values($this->attachments->toArray());

        usort($attachments, static fn(MessageAttachment $a, MessageAttachment $b): int => $a->position() <=> $b->position());

        return $attachments;
    }

    public function attachmentOf(MessageAttachmentId $id): ?MessageAttachment
    {
        foreach ($this->attachments as $attachment) {
            if ($attachment->id()->equals($id)) {
                return $attachment;
            }
        }

        return null;
    }

    public function attach(MessageAttachmentDraft $draft): void
    {
        $this->attachments->add(MessageAttachment::fromDraft($this, $this->attachments->count() + 1, $draft));
    }

    public function complete(string $text, MessageKind $kind, ?ActionPlanId $planId, AssistantUsage $usage, \DateTimeImmutable $now): void
    {
        $this->status = MessageStatus::Completed;
        $this->text = $text;
        $this->kind = $kind;
        $this->planId = $planId ?? $this->planId;
        $this->usage = $usage;
        $this->error = null;
        $this->completedAt = $now;
    }

    public function fail(string $error, AssistantUsage $usage, \DateTimeImmutable $now): void
    {
        $this->status = MessageStatus::Failed;
        $this->error = $error;
        $this->usage = $usage;
        $this->completedAt = $now;
    }

    public function attachPlan(ActionPlanId $planId): void
    {
        $this->planId = $planId;
    }
}
