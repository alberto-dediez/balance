<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EconomicSector;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Shared\Domain\Model\Locale\Locale;

final class EconomicSectorTranslated extends DomainEvent
{
    protected static string $eventName = 'economicSectorTranslated';

    public function __construct(
        private EconomicSectorId $economicSectorId,
        private string $name,
        private Locale $locale,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function economicSectorId(): EconomicSectorId
    {
        return $this->economicSectorId;
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
        return $this->economicSectorId();
    }

    public function payload(): array
    {
        return [
            'economicSectorId' => (string) $this->economicSectorId,
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

        return new EconomicSectorTranslated(
            economicSectorId: new EconomicSectorId($payload['economicSectorId']),
            name: $payload['name'],
            locale: $locale,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
