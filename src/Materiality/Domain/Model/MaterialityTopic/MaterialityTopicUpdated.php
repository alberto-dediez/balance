<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityTopic;

use App\Materiality\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MaterialityTopicUpdated extends DomainEvent
{
    protected static string $eventName = 'materialityTopicUpdated';

    public function __construct(
        private MaterialityTopicId $materialityTopicId,
        private MaterialityScope $scope,
        private ?MaterialityTopicDirection $direction,
        private string $name,
        private string $description,
        private int $position,
        private bool $active,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function materialityTopicId(): MaterialityTopicId
    {
        return $this->materialityTopicId;
    }

    public function scope(): MaterialityScope
    {
        return $this->scope;
    }

    public function direction(): ?MaterialityTopicDirection
    {
        return $this->direction;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function active(): bool
    {
        return $this->active;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->materialityTopicId();
    }

    public function payload(): array
    {
        return [
            'materialityTopicId' => (string) $this->materialityTopicId,
            'scope' => $this->scope->value,
            'direction' => $this->direction?->value,
            'name' => $this->name,
            'description' => $this->description,
            'position' => $this->position,
            'active' => $this->active,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MaterialityTopicUpdated(
            materialityTopicId: new MaterialityTopicId($payload['materialityTopicId']),
            scope: MaterialityScope::from($payload['scope']),
            direction: MaterialityTopicDirection::tryFrom((string) $payload['direction']),
            name: $payload['name'],
            description: $payload['description'],
            position: (int) $payload['position'],
            active: (bool) $payload['active'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
