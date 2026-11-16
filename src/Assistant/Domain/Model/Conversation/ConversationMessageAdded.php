<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\Conversation;

use App\Assistant\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ConversationMessageAdded extends DomainEvent
{
    protected static string $eventName = 'conversationMessageAdded';

    public function __construct(
        private readonly ConversationId $conversationId,
        private readonly MessageId $messageId,
        private readonly MessageRole $role,
        private readonly MessageKind $kind,
        private readonly int $attachmentsCount = 0,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function conversationId(): ConversationId
    {
        return $this->conversationId;
    }

    public function messageId(): MessageId
    {
        return $this->messageId;
    }

    public function role(): MessageRole
    {
        return $this->role;
    }

    public function kind(): MessageKind
    {
        return $this->kind;
    }

    public function attachmentsCount(): int
    {
        return $this->attachmentsCount;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->conversationId;
    }

    public function payload(): array
    {
        return [
            'conversationId' => (string) $this->conversationId,
            'messageId' => (string) $this->messageId,
            'role' => $this->role->value,
            'kind' => $this->kind->value,
            'attachmentsCount' => $this->attachmentsCount,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ConversationMessageAdded(
            conversationId: new ConversationId((string) $payload['conversationId']),
            messageId: new MessageId((string) $payload['messageId']),
            role: MessageRole::from((string) $payload['role']),
            kind: MessageKind::from((string) $payload['kind']),
            attachmentsCount: (int) ($payload['attachmentsCount'] ?? 0),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
