<?php

namespace App\Waste\Domain\Model\Waste;

use DateTimeImmutable;
use App\Waste\Domain\DomainEvent;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\AggregateRootId;

final class WasteTranslated extends DomainEvent
{
    protected static string $eventName = 'wasteTranslated';

    public function __construct(
        private WasteId $wasteId, 
        private string $name, 
        private Locale $locale,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function wasteId(): WasteId
    {
        return $this->wasteId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function aggregateRootId(): ?AggregateRootId
    {
        return $this->wasteId();
    }

    public function payload(): ?array
    {
        return [
            'wasteId' => (string) $this->wasteId,
            'name' => $this->name,
            'locale' => $this->locale->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new WasteTranslated(
            wasteId: new WasteId($payload['wasteId']), 
            name: $payload['name'],
            locale: Locale::tryFrom($payload['locale']),
            id: $id,
            occurredOn: $occurredOn
        );
    }
}