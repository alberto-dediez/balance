<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OvernightStay;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class OvernightStayUpdated extends DomainEvent
{
    protected static string $eventName = 'overnightStayUpdated';

    public function __construct(
        private OvernightStayId $overnightStayId,
        private string $name,
        private CategoryOvernight $category,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function overnightStayId(): OvernightStayId
    {
        return $this->overnightStayId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function category(): CategoryOvernight
    {
        return $this->category;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->overnightStayId();
    }

    public function payload(): array
    {
        return [
            'overnightStayId' => (string) $this->overnightStayId,
            'name' => $this->name,
            'category' => $this->category->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new OvernightStayUpdated(
            overnightStayId: new OvernightStayId($payload['overnightStayId']),
            name: $payload['name'],
            category: CategoryOvernight::tryFrom((string) ($payload['category'] ?? '')) ?? CategoryOvernight::Attendees,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
