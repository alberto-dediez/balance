<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\Conversation;

use App\Assistant\Domain\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ConversationStarted extends DomainEvent
{
    protected static string $eventName = 'conversationStarted';

    public function __construct(
        private readonly ConversationId $conversationId,
        private readonly UserId $userId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function conversationId(): ConversationId
    {
        return $this->conversationId;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->conversationId;
    }

    public function payload(): array
    {
        return [
            'conversationId' => (string) $this->conversationId,
            'userId' => (string) $this->userId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ConversationStarted(
            conversationId: new ConversationId((string) $payload['conversationId']),
            userId: new UserId((string) $payload['userId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
