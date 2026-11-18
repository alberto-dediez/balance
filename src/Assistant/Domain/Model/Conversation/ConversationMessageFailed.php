<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\Conversation;

use App\Assistant\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ConversationMessageFailed extends DomainEvent
{
    protected static string $eventName = 'conversationMessageFailed';

    public function __construct(
        private readonly ConversationId $conversationId,
        private readonly MessageId $messageId,
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

    public function aggregateRootId(): AggregateRootId
    {
        return $this->conversationId;
    }

    public function payload(): array
    {
        return [
            'conversationId' => (string) $this->conversationId,
            'messageId' => (string) $this->messageId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ConversationMessageFailed(
            conversationId: new ConversationId((string) $payload['conversationId']),
            messageId: new MessageId((string) $payload['messageId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
