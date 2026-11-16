<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\Conversation;

use App\Assistant\Domain\DomainEvent;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ConversationMessageCompleted extends DomainEvent
{
    protected static string $eventName = 'conversationMessageCompleted';

    public function __construct(
        private readonly ConversationId $conversationId,
        private readonly MessageId $messageId,
        private readonly MessageKind $kind,
        private readonly ?ActionPlanId $planId,
        private readonly int $toolCalls,
        private readonly string $costUsd,
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

    public function kind(): MessageKind
    {
        return $this->kind;
    }

    public function planId(): ?ActionPlanId
    {
        return $this->planId;
    }

    public function toolCalls(): int
    {
        return $this->toolCalls;
    }

    public function costUsd(): string
    {
        return $this->costUsd;
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
            'kind' => $this->kind->value,
            'planId' => null === $this->planId ? null : (string) $this->planId,
            'toolCalls' => $this->toolCalls,
            'costUsd' => $this->costUsd,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ConversationMessageCompleted(
            conversationId: new ConversationId((string) $payload['conversationId']),
            messageId: new MessageId((string) $payload['messageId']),
            kind: MessageKind::from((string) $payload['kind']),
            planId: isset($payload['planId']) ? new ActionPlanId((string) $payload['planId']) : null,
            toolCalls: (int) $payload['toolCalls'],
            costUsd: (string) $payload['costUsd'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
