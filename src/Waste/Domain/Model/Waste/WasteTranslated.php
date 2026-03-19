<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Waste;

use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;
use App\Waste\Domain\DomainEvent;

final class WasteTranslated extends DomainEvent
{
    protected static string $eventName = 'wasteTranslated';

    public function __construct(
        private readonly WasteId $wasteId,
        private readonly string $name,
        private readonly Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
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

    public function aggregateRootId(): AggregateRootId
    {
        return $this->wasteId;
    }

    public function payload(): array
    {
        return [
            'wasteId' => (string) $this->wasteId,
            'name' => $this->name,
            'locale' => $this->locale->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new WasteTranslated(
            wasteId: new WasteId($payload['wasteId']),
            name: $payload['name'],
            locale: Locale::tryFrom($payload['locale']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
