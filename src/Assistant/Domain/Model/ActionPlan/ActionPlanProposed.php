<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\ActionPlan;

use App\Assistant\Domain\DomainEvent;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ActionPlanProposed extends DomainEvent
{
    protected static string $eventName = 'actionPlanProposed';

    /**
     * @param list<array{stepId: string, position: int, tool: string, label: string, arguments: list<array{name: string, value: string, display: ?string, fromStep: ?int}>}> $steps
     */
    public function __construct(
        private readonly ActionPlanId $planId,
        private readonly ConversationId $conversationId,
        private readonly UserId $userId,
        private readonly string $summary,
        private readonly string $timezone,
        private readonly array $steps,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function planId(): ActionPlanId
    {
        return $this->planId;
    }

    public function conversationId(): ConversationId
    {
        return $this->conversationId;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function summary(): string
    {
        return $this->summary;
    }

    public function timezone(): string
    {
        return $this->timezone;
    }

    /**
     * @return list<array{stepId: string, position: int, tool: string, label: string, arguments: list<array{name: string, value: string, display: ?string, fromStep: ?int}>}>
     */
    public function steps(): array
    {
        return $this->steps;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->planId;
    }

    public function payload(): array
    {
        return [
            'planId' => (string) $this->planId,
            'conversationId' => (string) $this->conversationId,
            'userId' => (string) $this->userId,
            'summary' => $this->summary,
            'timezone' => $this->timezone,
            'steps' => $this->steps,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        /** @var list<array{stepId: string, position: int, tool: string, label: string, arguments: list<array{name: string, value: string, display: ?string, fromStep: ?int}>}> $steps */
        $steps = is_array($payload['steps'] ?? null) ? $payload['steps'] : [];

        return new ActionPlanProposed(
            planId: new ActionPlanId((string) $payload['planId']),
            conversationId: new ConversationId((string) $payload['conversationId']),
            userId: new UserId((string) $payload['userId']),
            summary: (string) $payload['summary'],
            timezone: (string) $payload['timezone'],
            steps: $steps,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
