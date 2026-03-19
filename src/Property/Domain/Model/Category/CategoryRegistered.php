<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Category;

use App\Property\Domain\DomainEvent;
use App\Property\Domain\Model\Property\ExternalId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CategoryRegistered extends DomainEvent
{
    protected static string $eventName = 'categoryRegistered';

    public function __construct(
        private readonly CategoryId $categoryId,
        private readonly string $name,
        private readonly ?ExternalId $externalId = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function categoryId(): CategoryId
    {
        return $this->categoryId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function externalId(): ?ExternalId
    {
        return $this->externalId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->categoryId;
    }

    public function payload(): array
    {
        return [
            'categoryId' => (string) $this->categoryId,
            'name' => $this->name,
            'externalId' => $this->externalId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CategoryRegistered(
            categoryId: new CategoryId($payload['categoryId']),
            name: $payload['name'],
            externalId: $payload['externalId'] ?? null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
