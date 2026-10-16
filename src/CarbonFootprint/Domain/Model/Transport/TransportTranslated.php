<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Transport;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class TransportTranslated extends DomainEvent
{
    protected static string $eventName = 'transportTranslated';

    public function __construct(
        private TransportId $transportId,
        private string $name,
        private Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function transportId(): TransportId
    {
        return $this->transportId;
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
        return $this->transportId();
    }

    public function payload(): array
    {
        return [
            'transportId' => (string) $this->transportId,
            'name' => $this->name,
            'locale' => $this->locale->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $locale = Locale::tryFrom($payload['locale']);
        assert(null !== $locale);

        return new TransportTranslated(
            transportId: new TransportId($payload['transportId'] ?? $payload['roadTransportId']),
            name: $payload['name'],
            locale: $locale,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
