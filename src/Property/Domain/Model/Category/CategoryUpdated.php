<?php

namespace App\Property\Domain\Model\Category;

use DateTimeImmutable;
use App\Property\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Property\Domain\Model\Category\CategoryId;
use App\Property\Domain\Model\Property\ExternalId;

final class CategoryUpdated extends DomainEvent
{
    protected static string $eventName = 'categoryUpdated';

    public function __construct(
        private CategoryId $categoryId, 
        private string $name, 
        private ?ExternalId $externalId = null,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
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

    public function payload(): ?array
    {
        return [
            'categoryId' => (string) $this->categoryId,
            'name' => $this->name,
            'externalId' => $this->externalId
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new CategoryUpdated(
            categoryId: new CategoryId($payload['categoryId']), 
            name: $payload['name'],
            externalId: isset($payload['externalId']) ? $payload['externalId'] : null,
            id: $id,
            occurredOn: $occurredOn
        );
    }
}