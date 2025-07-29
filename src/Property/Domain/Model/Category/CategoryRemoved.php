<?php

namespace App\Property\Domain\Model\Category;

use DateTimeImmutable;
use App\Property\Domain\DomainEvent;
use App\Property\Domain\Model\Category\CategoryId;
use App\Shared\Domain\Event\DomainEventId;

final class CategoryRemoved extends DomainEvent
{
    protected static string $eventName = 'categoryRemoved';

    public function __construct(
        private CategoryId $categoryId, 
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function categoryId(): CategoryId
    {
        return $this->categoryId;
    }

    public function payload(): ?array
    {
        return [
            'categoryId' => (string) $this->categoryId
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new CategoryRemoved(
            categoryId: new CategoryId($payload['categoryId']),
            id: $id,
            occurredOn: $occurredOn
        );
    }
}